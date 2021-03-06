<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Framework\EntityManager\Operation\Write;

use Magento\Framework\EntityManager\Operation\Write\Create\CreateMain;
use Magento\Framework\EntityManager\Operation\Write\Create\CreateAttributes;
use Magento\Framework\EntityManager\Operation\Write\Create\CreateExtensions;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EventManager;

/**
 * Class Create
 */
class Create
{
    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var CreateMain
     */
    private $createMain;

    /**
     * @var CreateAttributes
     */
    private $createAttributes;

    /**
     * @var CreateExtensions
     */
    private $createExtensions;

    /**
     * Create constructor.
     *
     * @param MetadataPool $metadataPool
     * @param ResourceConnection $resourceConnection
     * @param EventManager $eventManager
     * @param CreateMain $createMain
     * @param CreateAttributes $createAttributes
     * @param CreateExtensions $createExtensions
     */
    public function __construct(
        MetadataPool $metadataPool,
        ResourceConnection $resourceConnection,
        EventManager $eventManager,
        CreateMain $createMain,
        CreateAttributes $createAttributes,
        CreateExtensions $createExtensions
    ) {
        $this->metadataPool = $metadataPool;
        $this->resourceConnection = $resourceConnection;
        $this->eventManager = $eventManager;
        $this->createMain = $createMain;
        $this->createAttributes = $createAttributes;
        $this->createExtensions = $createExtensions;
    }

    /**
     * @param string $entityType
     * @param object $entity
     * @param array $arguments
     * @return object
     * @throws \Exception
     */
    public function execute($entityType, $entity, $arguments = [])
    {
        $metadata = $this->metadataPool->getMetadata($entityType);
        $connection = $this->resourceConnection->getConnectionByName($metadata->getEntityConnectionName());
        $connection->beginTransaction();
        try {
            $this->eventManager->dispatch(
                'entity_manager_save_before',
                [
                    'entity_type' => $entityType,
                    'entity' => $entity
                ]
            );
            $this->eventManager->dispatchEntityEvent($entityType, 'save_before', ['entity' => $entity]);
            $entity = $this->createMain->execute($entityType, $entity, $arguments);
            $entity = $this->createAttributes->execute($entityType, $entity, $arguments);
            $entity = $this->createExtensions->execute($entityType, $entity, $arguments);
            $this->eventManager->dispatchEntityEvent($entityType, 'save_after', ['entity' => $entity]);
            $this->eventManager->dispatch(
                'entity_manager_save_after',
                [
                    'entity_type' => $entityType,
                    'entity' => $entity
                ]
            );
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
        return $entity;
    }
}

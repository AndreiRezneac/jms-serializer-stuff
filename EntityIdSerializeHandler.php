<?php
declare(strict_types=1);

namespace PC\ContactManagementBundle\BLL;

use Doctrine\ORM\EntityManager;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\JsonDeserializationVisitor;

class EntityIdSerializeHandler implements SubscribingHandlerInterface
{
    /** @var EntityManager */
    private $em;
    
    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
    
    /**
     * Return format:
     *
     *      array(
     *          array(
     *              'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
     *              'format' => 'json',
     *              'type' => 'DateTime',
     *              'method' => 'serializeDateTimeToJson',
     *          ),
     *      )
     *
     * The direction and method keys can be omitted.
     *
     * @return array
     */
    public static function getSubscribingMethods(): array
    {
        return [
            [
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format'    => 'json',
                'type'      => 'EntityId',
                'method'    => 'serializeEntityToId',
            ],
            [
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'format'    => 'json',
                'type'      => 'EntityId',
                'method'    => 'deserializeIdToEntity',
            ],
        ];
    }
    
    /**
     * @param JsonDeserializationVisitor $visitor
     * @param int|null                   $id
     * @param array                      $type
     *
     * @return bool|\Doctrine\Common\Proxy\Proxy|null|object
     * @throws \Doctrine\ORM\ORMException
     */
    public function deserializeIdToEntity(JsonDeserializationVisitor $visitor, $id, array $type)
    {
        $visitor;
        if ($id === null) {
            return null;
        }
        $entityName = $this->getEntityName($type);
        
        return $this->em->getReference($entityName, $id);
    }
    
    /**
     * @param array $type
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    private function getEntityName(array $type): string
    {
        $this->throwExceptionOnNoEntityName($type);
        
        return $type['params'][0];
    }
    
    /** @param array $type */
    private function throwExceptionOnNoEntityName(array $type): void
    {
        if (empty($type['params'][0]) || ! is_string($type['params'][0])) {
            throw new \InvalidArgumentException(
                'You must specify entityName in @JMS\Type("EntityId<\'entity:name\'>") annotation.'
            );
        }
    }
    
    /**
     * @param JsonSerializationVisitor $visitor
     * @param object|null              $entity
     * @param array                    $type
     *
     * @return mixed
     * @throws \Exception
     */
    public function serializeEntityToId(JsonSerializationVisitor $visitor, $entity, array $type)
    {
        $visitor;
        $entityName = $this->getEntityName($type);
        if ($entity === null) {
            return null;
        }
        $this->throwExceptionOnNonObjectEntity($entity, $entityName);
        $identifier = $this->getEntityIdentifier($type);
        $getter     = 'get'.ucfirst($identifier);
        $id         = $entity->$getter();
        
        return $id;
    }
    
    /**
     * @param array $type
     *
     * @return string
     * @throws \Exception
     */
    private function getEntityIdentifier(array $type)
    {
        $entityName    = $this->getEntityName($type);
        $classMetadata = $classMetadata = $this->em->getClassMetadata($entityName);
        $this->throwExceptionOnNoClassMetadata($classMetadata, $entityName);
        $identifiers   = $classMetadata->getIdentifier();
        $this->throwExceptionOnMultipleIdentifiers($identifiers, $entityName);
    
        return current($identifiers);
    }
    
    /**
     * @param $classMetadata
     * @param $entityName
     *
     * @throws \Exception
     */
    private function throwExceptionOnNoClassMetadata($classMetadata, string $entityName)
    {
        if (!$classMetadata) {
            throw new \Exception(sprintf('Can\'t find metadata for class %s', $entityName));
        }
    }
    
    /**
     * @param array $identifiers
     * @param $entityName
     *
     * @throws \Exception
     */
    private function throwExceptionOnMultipleIdentifiers(array $identifiers, $entityName): void
    {
        if (count($identifiers) != 1) {
            throw new \Exception(
                sprintf(
                    '@JMS\Type("EntityId<>") supports entities with only one identifier, %s contains %s identifier(s).',
                    $entityName,
                    count($identifiers)
                )
            );
        }
    }
    
    /**
     * @param        $entity
     * @param string $entityName
     *
     * @throws \InvalidArgumentException
     */
    private function throwExceptionOnNonObjectEntity($entity, string $entityName): void
    {
        if (! is_object($entity)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Value of @JMS\Type("EntityId<\'%s\'>") must be object or null, %s given.',
                    $entityName,
                    gettype($entity)
                )
            );
        }
    }
}

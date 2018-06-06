# jms-serializer-stuff
jms serializer tricks

serializes entity to id and viceversa

usage:
/**
 * @JMS\Type("EntityId<'FOO\BundleName\Entity\EntityName'>")
 */
  
even works nested (eg with ArrayCollection) 
usage:
/**
 * @JMS\Type("ArrayCollection<EntityId<'FOO\BundleName\Entity\EntityName'>>")
 */
 
 

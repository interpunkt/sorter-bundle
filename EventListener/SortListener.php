<?php

namespace Ip\SorterBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Ip\SorterBundle\Model\AbstractSort;

class SortListener
{
    /**
     * @param AbstractSort       $item
     * @param LifecycleEventArgs $event
     */
    public function prePersist(AbstractSort $item, LifecycleEventArgs $event)
    {
        $maxSortRank = $this->getMaxSort($event, $item);
        $item->setSort($maxSortRank + 1);
    }

    /**
     * @param AbstractSort $item
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(AbstractSort $item, PreUpdateEventArgs $args)
    {
        foreach ($item->hasSuperCategory() as $superCategoryName => $superCategoryItem) {
            if ($args->hasChangedField($superCategoryName)) {
                // update sort values of old superCategory
                $oldValue = $args->getOldValue($superCategoryName);
                $this->updateItemsWithHigherSortNumber($args, $item, [$superCategoryName => $oldValue->getId()]);

                // set max sort of new superCategory
                $maxSortRank = $this->getMaxSort($args, $item);
                $item->setSort($maxSortRank + 1);
            }
        }
    }

    /**
     * @param AbstractSort       $item
     * @param LifecycleEventArgs $event
     */
    public function preRemove(AbstractSort $item, LifecycleEventArgs $event)
    {
        $this->updateItemsWithHigherSortNumber($event, $item);
    }

    /**
     * @param LifecycleEventArgs | PreUpdateEventArgs  $event
     * @param AbstractSort       $item
     * @return int
     */
    private function getMaxSort(&$event, $item)
    {
        $em = $event->getEntityManager();
        $entityClass = get_class($item);

        $otherItem = $em->getRepository($entityClass)
            ->findOneBy(
                $item->hasSuperCategory(),
                ["sort" => "DESC"]
            );

        return (is_null($otherItem)) ? 0 : $otherItem->getSort();
    }

    /**
     * @param LifecycleEventArgs | PreUpdateEventArgs $event
     * @param AbstractSort $item
     * @param array $replacement
     *
     * Every item, with a higher sort value than the deleted item,
     * has the sort value reduced by 1 to avoid gaps
     */
    private function updateItemsWithHigherSortNumber(&$event, AbstractSort &$item, $replacement = array())
    {
        $em = $event->getEntityManager();
        $entityClass = get_class($event->getEntity());

        $sortRank = $item->getSort();

        $superCategoryCondition = '';

        foreach ($item->hasSuperCategory() as $key => $value) {
            if (array_key_exists($key, $replacement)) {
                $valueId = $replacement[$key];
            }
            else {
                $valueId = $value->getId();
            }

            $superCategoryCondition .= "i.$key = $valueId AND ";
        }

        $query = $em->createQuery(
            "SELECT i 
             FROM $entityClass i
             WHERE $superCategoryCondition i.sort > :sort"
        )->setParameter('sort', $sortRank);

        $itemsWithHigherSortNumber = $query->getResult();

        foreach ($itemsWithHigherSortNumber as $item) {
            $newSort = $item->getSort() - 1;

            /*
             * DQL is used here to avoid maximum function nesting error in XDebug
            */
            $updateQuery = $em->createQuery(
                "UPDATE $entityClass i 
                 SET i.sort = :sort
                 WHERE i.id = :id"
            )->setParameter('sort', $newSort)
                ->setParameter('id', $item->getId());

            $updateQuery->execute();
        }
    }
}

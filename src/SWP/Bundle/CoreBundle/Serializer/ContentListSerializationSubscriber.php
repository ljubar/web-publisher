<?php

declare(strict_types=1);

/*
 * This file is part of the Superdesk Web Publisher Core Bundle.
 *
 * Copyright 2019 Sourcefabric z.ú. and contributors.
 *
 * For the full copyright and license information, please see the
 * AUTHORS and LICENSE files distributed with this source code.
 *
 * @copyright 2019 Sourcefabric z.ú
 * @license http://www.superdesk.org/license
 */

namespace SWP\Bundle\CoreBundle\Serializer;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use SWP\Bundle\ContentListBundle\Form\Type\ContentListType;
use SWP\Bundle\CoreBundle\Model\ContentList;
use SWP\Component\Common\Criteria\Criteria;
use SWP\Bundle\CoreBundle\Model\ContentListInterface;
use SWP\Component\ContentList\Repository\ContentListItemRepositoryInterface;

final class ContentListSerializationSubscriber implements EventSubscriberInterface
{
    private $contentListItemRepository;

    public function __construct(ContentListItemRepositoryInterface $contentListItemRepository)
    {
        $this->contentListItemRepository = $contentListItemRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => 'serializer.pre_serialize',
                'class' => ContentList::class,
                'method' => 'onPreSerialize',
            ],
        ];
    }

    public function onPreSerialize(ObjectEvent $event)
    {
        /** @var ContentListInterface $object */
        $object = $event->getObject();
        if (!$object instanceof ContentListInterface) {
            return;
        }

        $object->setFilters(ContentListType::transformArrayKeys($object->getFilters(), 'snake'));

        $items = $this->contentListItemRepository->getQueryByCriteria(new Criteria([
            'contentList' => $object,
        ]), ['createdAt' => 'desc'], 'n')->setMaxResults(5)->getQuery()->getResult();
        $object->setItems($items);
    }
}

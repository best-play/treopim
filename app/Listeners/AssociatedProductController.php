<?php
/**
 * Pim
 * Free Extension
 * Copyright (c) TreoLabs GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Pim\Listeners;

use Treo\Listeners\AbstractListener;
use Treo\Core\EventManager\Event;

/**
 * Class AssociatedProductController
 *
 * @author r.zablodskiy@treolabs.com
 */
class AssociatedProductController extends AbstractListener
{
    /**
     * Before action list
     *
     * @param Event $event
     */
    public function beforeActionList(Event $event)
    {
        // get where
        $where = $event->getArgument('request')->get('where', []);
        //merge current "where" with whereProductTypes
        $where = array_merge($where, $this->getWhereProductType());

        $event->getArgument('request')->setQuery('where', $where);
    }

    /**
     * After action list
     *
     * @param Event $event
     */
    public function afterActionList(Event $event)
    {
        $result = $event->getArgument('result');
        $result['list'] = $this->setAssociatedProductsImage((array)$result['list']);
        $event->setArgument('result', $result);
    }

    /**
     * After action read
     *
     * @param Event $event
     */
    public function afterActionRead(Event $event)
    {
        $event->setArgument('result', $this->setAssociatedProductsImage((array)$event->getArgument('result')));
    }

    /**
     * Set main images for associated products
     *
     * @param array $result
     *
     * @return \stdClass
     */
    protected function setAssociatedProductsImage(array $result): array
    {
        // prepare products ids
        $productIds = [];
        foreach ($result as $item) {
            if (isset($item->{'mainProductId'}) && !in_array($item->{'mainProductId'}, $productIds)) {
                $productIds[] = $item->{'mainProductId'};
            }

            if (isset($item->{'relatedProductId'}) && !in_array($item->{'relatedProductId'}, $productIds)) {
                $productIds[] = $item->{'relatedProductId'};
            }
        }

        // get product images
        $data = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->select(['id', 'imageId'])
            ->where(['id' => $productIds])
            ->find()
            ->toArray();

        // prepare images
        $images = array_column($data, 'imageId', 'id');

        foreach ($result as $key => $item) {
            if ($images[$item->mainProductId]) {
                $result[$key]->{'mainProductImageId'} = !empty($images[$item->mainProductId]) ? $images[$item->mainProductId] : null;
            }

            if ($images[$item->relatedProductId]) {
                $result[$key]->{'relatedProductImageId'} = !empty($images[$item->relatedProductId]) ? $images[$item->relatedProductId] : null;
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getWhereProductType(): array
    {
        // prepare types
        $types = array_keys(
            $this->getContainer()
                ->get('metadata')
                ->get('pim.productType'));

        return [
            [
                'type' => 'in',
                'attribute' => 'mainProduct.type',
                'value' => $types
            ],
            [
                'type' => 'in',
                'attribute' => 'relatedProduct.type',
                'value' => $types
            ]
        ];
    }
}

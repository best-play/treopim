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

declare(strict_types=1);

namespace Pim\Listeners;

use Espo\Core\Exceptions\BadRequest;
use Espo\ORM\Entity;
use Treo\Core\EventManager\Event;
use Treo\Listeners\AbstractListener;

/**
 * Class ProductFamilyAttributeEntity
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ProductFamilyAttributeEntity extends AbstractListener
{
    /**
     * @param Event $event
     *
     * @throws BadRequest
     */
    public function beforeSave(Event $event)
    {
        // prepare data
        $entity = $event->getArgument('entity');
        $options = $event->getArgument('options');

        // exit
        if (!empty($options['skipValidation'])) {
            return true;
        }

        if (empty($productFamily = $entity->get('productFamily')) || empty($attribute = $entity->get('attribute'))) {
            throw new BadRequest($this->exception('ProductFamily and Attribute cannot be empty'));
        }

        if (!$this->isUnique($entity)) {
            throw new BadRequest($this->exception('Such record already exists'));
        }

        if ($this->hasSuchProduct($entity)) {
            throw new BadRequest($this->exception('Product Family has Product with same Attribute property'));
        }

        // clearing channels ids
        if ($entity->get('scope') == 'Global') {
            $entity->set('channelsIds', []);
        }
    }

    /**
     * @param Event $event
     */
    public function afterSave(Event $event)
    {
        // prepare data
        $entity = $event->getArgument('entity');

        // update product attribute value
        $this->updateProductAttributeValues($entity);
    }

    /**
     * @param Event $event
     */
    public function afterRemove(Event $event)
    {
        // prepare data
        $entity = $event->getArgument('entity');

        $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->removeCollectionByProductFamilyAttribute($entity->get('id'));
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function isUnique(Entity $entity): bool
    {
        // prepare count
        $count = 0;

        if ($entity->get('scope') == 'Global') {
            $count = $this
                ->getEntityManager()
                ->getRepository('ProductFamilyAttribute')
                ->where(
                    [
                        'id!='            => $entity->get('id'),
                        'productFamilyId' => $entity->get('productFamilyId'),
                        'attributeId'     => $entity->get('attributeId'),
                        'scope'           => 'Global',
                    ]
                )
                ->count();
        }

        if ($entity->get('scope') == 'Channel') {
            $count = $this
                ->getEntityManager()
                ->getRepository('ProductFamilyAttribute')
                ->distinct()
                ->join('channels')
                ->where(
                    [
                        'id!='            => $entity->get('id'),
                        'productFamilyId' => $entity->get('productFamilyId'),
                        'attributeId'     => $entity->get('attributeId'),
                        'scope'           => 'Channel',
                        'channels.id'     => $entity->get('channelsIds'),
                    ]
                )
                ->count();
        }

        return empty($count);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function updateProductAttributeValues(Entity $entity): bool
    {
        // get products
        $products = $this
            ->getEntityManager()
            ->getRepository('Product')
            ->where(['productFamilyId' => $entity->get('productFamilyId')])
            ->find();

        // exit
        if (count($products) == 0) {
            return true;
        }

        // get exists
        $exists = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(
                [
                    'productId'   => array_column($products->toArray(), 'id'),
                    'attributeId' => $entity->get('attributeId')
                ]
            )
            ->find();

        // prepare channels for entity
        $channels = array_column($entity->get('channels')->toArray(), 'id');
        sort($channels);

        foreach ($products as $product) {
            // prepare product attribute value
            $productAttributeValue = null;

            // find related
            foreach ($exists as $exist) {
                if ($exist->get('productFamilyAttributeId') == $entity->get('id') && $product->get('id') == $exist->get('productId')) {
                    $productAttributeValue = $exist;
                    break;
                }
            }

            // find not related
            if (empty($productAttributeValue)) {
                foreach ($exists as $exist) {
                    // prepare channels for exist
                    $existChannels = array_column($exist->get('channels')->toArray(), 'id');
                    sort($existChannels);

                    if ($product->get('id') == $exist->get('productId')
                        && $entity->get('attributeId') == $exist->get('attributeId')
                        && $entity->get('scope') == $exist->get('scope')
                        && empty($exist->get('productFamilyAttributeId'))
                        && ($entity->get('scope') == 'Global' || ($entity->get('scope') == 'Channel' && $channels == $existChannels))) {

                        $productAttributeValue = $exist;
                        $productAttributeValue->set('productFamilyAttributeId', $entity->get('id'));
                        break;
                    }
                }
            }

            // create new product attribute value if it needs
            if (empty($productAttributeValue)) {
                if ($product->get('type') == 'productVariant') {
                    continue;
                }

                $productAttributeValue = $this->getEntityManager()->getEntity('ProductAttributeValue');
                $productAttributeValue->set('productId', $product->get('id'));
                $productAttributeValue->set('attributeId', $entity->get('attributeId'));
                $productAttributeValue->set('productFamilyAttributeId', $entity->get('id'));
                $productAttributeValue->set('ownerUserId', $entity->get('ownerUserId'));
                $productAttributeValue->set('assignedUserId', $entity->get('assignedUserId'));
                $productAttributeValue->set('teamsIds', $entity->get('teamsIds'));
            }

            $productAttributeValue->set('scope', $entity->get('scope'));
            $productAttributeValue->set('isRequired', $entity->get('isRequired'));
            $productAttributeValue->set('channelsIds', $entity->get('channelsIds'));

            $this->getEntityManager()->saveEntity(
                $productAttributeValue,
                ['skipProductAttributeValueHook' => true, 'productFamilyAttributeChanged' => true]
            );
        }

        return true;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    protected function hasSuchProduct(Entity $entity): bool
    {
        // get products
        if (empty($products = $entity->get('productFamily')->get('products')->toArray())) {
            return false;
        }

        // get product attribute values
        $productAttributeValues = $this
            ->getEntityManager()
            ->getRepository('ProductAttributeValue')
            ->where(['productId' => array_column($products, 'id')])
            ->find();

        if (count($productAttributeValues) == 0) {
            return false;
        }

        foreach ($productAttributeValues as $productAttributeValue) {
            if (empty($productAttributeValue->get('productFamilyAttributeId'))
                && $productAttributeValue->get('scope') == $entity->get('scope')
                && $productAttributeValue->get('attributeId') == $entity->get('attributeId')
            ) {
                if ($entity->get('scope') == 'Global') {
                    return true;
                }

                if ($entity->get('scope') == 'Channel') {
                    foreach ($productAttributeValue->get('channels')->toArray() as $channel) {
                        if (in_array($channel['id'], $entity->get('channelsIds'))) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function exception(string $key): string
    {
        return $this->getLanguage()->translate($key, 'exceptions', 'ProductFamilyAttribute');
    }
}

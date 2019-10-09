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

namespace Pim\Services;

/**
 * Service of Channel
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Channel extends AbstractService
{
    /**
     * Get product data for channel
     *
     * @param string $channelId
     *
     * @return array
     */
    public function getProducts(string $channelId): array
    {
        // prepare result
        $result = [];

        if (!empty($products = $this->getChannelCategoryProducts($channelId))) {
            foreach ($products as $product) {
                // prepare categories
                $categories = [];
                if (isset($result[$product['productId']]['categories'])) {
                    $categories = $result[$product['productId']]['categories'];
                }
                if (!empty($product['categoryName'])) {
                    $categories[] = $product['categoryName'];
                }

                $result[$product['productId']] = [
                    'productId'   => (string)$product['productId'],
                    'productName' => (string)$product['productName'],
                    'isActive'    => (bool)$product['isActive'],
                    'categories'  => $categories
                ];
            }

            // prepare result
            $result = array_values($result);
        }

        return $result;
    }

    /**
     * Prepare locales
     *
     * @param array $locales
     *
     * @return array
     */
    public function prepareLocales(array $locales): array
    {
        // prepare result
        $result = [];

        // get system locales
        $systemLocales = $this->getConfig()->get('inputLanguageList');

        foreach ($locales as $locale) {
            if (in_array($locale, $systemLocales)) {
                $result[] = $locale;
            }
        }

        return $result;
    }

    /**
     * Init
     */
    protected function init()
    {
        parent::init();

        // add dependencies
        $this->addDependency('serviceFactory');
    }

    /**
     * Get channel category products data
     *
     * @param string $channelId
     *
     * @return array
     */
    protected function getChannelCategoryProducts(string $channelId): array
    {
        // prepare result
        $products = [];

        if (!empty($category = $this->getDBChannelCategory($channelId))) {
            // get categories
            $categories = $this->getCategoryChildren($category);
            $categories[] = $category;

            // get products
            $products = $this->getDBCategoriesProducts($categories);
        }

        return $products;
    }

    /**
     * Get channel category from DB
     *
     * @param string $channelId
     *
     * @return string
     */
    protected function getDBChannelCategory(string $channelId)
    {
        $sql
            = "SELECT
                  ct.category_id 
                FROM channel AS c
                JOIN catalog AS ct ON ct.id = c.catalog_id AND ct.deleted = 0 AND ct.is_active = 1
                WHERE c.deleted = 0 AND c.id = '{$channelId}'";
        $sth = $this->getEntityManager()->getPDO()->prepare($sql);
        $sth->execute();

        $data = $sth->fetch(\PDO::FETCH_ASSOC);

        return (!empty($data['category_id'])) ? $data['category_id'] : null;
    }

    /**
     * Get channel categories from DB
     *
     * @param array $categories
     *
     * @return array
     */
    protected function getDBCategoriesProducts(array $categories): array
    {
        // prepare data
        $where = $this->getAclWhereSql('Category', 'c');
        $pdo = $this->getEntityManager()->getPDO();

        $sql
            = "SELECT
                  c.id        AS categoryId,
                  c.name      AS categoryName,
                  p.id        AS productId,
                  p.name      AS productName,
                  p.is_active AS isActive
                FROM product_category_linker AS l
                 JOIN category AS c ON c.id = l.category_id
                 JOIN product AS p ON p.id = l.product_id AND p.deleted = 0
                WHERE l.deleted = 0 $where AND l.category_id IN (\"" . implode('","', $categories) . "\")";
        $sth = $pdo->prepare($sql);
        $sth->execute();

        return $sth->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param string $id
     *
     * @return array
     */
    protected function getCategoryChildren(string $id): array
    {
        if (empty($category = $this->getEntityManager()->getEntity('Category', $id))) {
            return [];
        }

        return array_column($category->getChildren()->toArray(), 'id');
    }
}

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

namespace Pim\Services;

use Espo\Core\Exceptions\Forbidden;

/**
 * Service of Category
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Category extends AbstractService
{
    /**
     * Get category entity
     *
     * @param string $id
     *
     * @return array
     * @throws Forbidden
     */
    public function getEntity($id = null)
    {
        // call parent
        $entity = parent::getEntity($id);

        // set hasChildren param
        $entity->set('hasChildren', $entity->hasChildren());

        return $entity;
    }

    /**
     * Is child category
     *
     * @param string $categoryId
     * @param string $selectedCategoryId
     *
     * @return bool
     */
    public function isChildCategory(string $categoryId, string $selectedCategoryId): bool
    {
        // get category
        if (empty($category = $this->getEntityManager()->getEntity('Category', $selectedCategoryId))) {
            return false;
        }

        return in_array($categoryId, explode("|", (string)$category->get('categoryRoute')));
    }

    /**
     * Get id parent category and ids children category
     *
     * @param string $id
     *
     * @return array
     * @throws \Espo\Core\Exceptions\Error
     */
    public function getIdsTree(string $id): array
    {
        /** @var \Pim\Entities\Category $category */
        $category = $this->getEntityManager()->getEntity('Category', $id);

        $categoriesIds = [];
        $categoriesChild = $category->getChildren()->toArray();

        if (!empty($categoriesChild)) {
            $categoriesChild = $category->getChildren()->toArray();
            $categoriesIds = array_column($categoriesChild, 'id');
        }

        $categoriesIds[] = $category->id;

        return $categoriesIds;
    }

    /**
     * Remove ProductCategory by ID category
     *
     * @param string $categoryId
     */
    public function removeProductCategoryByCategory(string $categoryId): void
    {
        $productsCategory = $this
            ->getEntityManager()
            ->getRepository('ProductCategory')
            ->where(['categoryId' => $categoryId])
            ->find()
            ->toArray();

        $serviceProduct = $this->getServiceFactory()->create('ProductCategory');

        foreach ($productsCategory as $productCategory) {
            $serviceProduct->deleteEntity($productCategory['id']);
        }
    }

    /**
     * After mass delete action
     *
     * @param array $idList
     *
     * @return void
     */
    protected function afterMassRemove(array $idList): void
    {
        foreach ($idList as $id) {
            $this->removeProductCategoryByCategory($id);
        }
        parent::afterMassRemove($idList);
    }

    /**
     * @inheritDoc
     */
    protected function getSelectParams($params)
    {
        // get parent
        $selectParams = parent::getSelectParams($params);

        // prepare additional select columns
        $additionalSelectColumns = [
            'childrenCount' => '(SELECT COUNT(c1.id) FROM category AS c1 WHERE c1.category_parent_id=category.id AND c1.deleted=0)'
        ];

        // add additional select columns
        foreach ($additionalSelectColumns as $alias => $sql) {
            $selectParams['additionalSelectColumns'][$sql] = $alias;
        }

        return $selectParams;
    }
}

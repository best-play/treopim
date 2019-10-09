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

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Templates\Services\Base;
use Espo\ORM\Entity;
use Treo\Core\EventManager\Event;
use Treo\Core\Utils\Util;

/**
 * Class of AbstractService
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
abstract class AbstractService extends Base
{
    /**
     * Get ACL "where" SQL
     *
     * @param string $entityName
     * @param string $entityAlias
     *
     * @return string
     */
    public function getAclWhereSql(string $entityName, string $entityAlias): string
    {
        // prepare sql
        $sql = '';

        if (!$this->getUser()->isAdmin()) {
            // prepare data
            $userId = $this->getUser()->get('id');

            if ($this->getAcl()->checkReadOnlyOwn($entityName)) {
                $sql .= " AND $entityAlias.assigned_user_id = '$userId'";
            }
            if ($this->getAcl()->checkReadOnlyTeam($entityName)) {
                $sql .= " AND $entityAlias.id IN ("
                    . "SELECT et.entity_id "
                    . "FROM entity_team AS et "
                    . "JOIN team_user AS tu ON tu.team_id=et.team_id "
                    . "WHERE et.deleted=0 AND tu.deleted=0 "
                    . "AND tu.user_id = '$userId' AND et.entity_type='$entityName')";
            }
        }

        return $sql;
    }

    /**
     * Init
     */
    protected function init()
    {
        parent::init();

        // add dependencies
        $this->addDependency('language');
        $this->addDependency('eventManager');
        $this->addDependency('metadata');
    }

    /**
     * @param Entity $entity
     * @param Entity $duplicatingEntity
     */
    protected function duplicatePimImages(Entity $entity, Entity $duplicatingEntity)
    {
        // get images
        if (!empty($images = $duplicatingEntity->get('pimImages'))) {
            // prepare repository
            $repository = $this->getEntityManager()->getRepository('PimImage');

            // copy images
            foreach ($images as $image) {
                // prepare new image
                $newImage = $repository->get();
                $newImage->set($image->toArray());
                $newImage->id = Util::generateId();
                $newImage->set(lcfirst($entity->getEntityName()) . 'Id', $entity->get('id'));

                // save
                $this->getEntityManager()->saveEntity($newImage);

                // get channels
                if (!empty($channels = $image->get('channels'))) {
                    foreach ($channels as $channel) {
                        // relate channel
                        $repository->relate($newImage, 'channels', $channel);
                    }
                }
            }
        }
    }

    /**
     * Get translated message
     *
     * @param string $label
     * @param string $category
     * @param string $scope
     * @param null   $requiredOptions
     *
     * @return string
     */
    protected function getTranslate(string $label, string $category, string $scope, $requiredOptions = null): string
    {
        return $this
            ->getInjection('language')
            ->translate($label, $category, $scope, $requiredOptions);
    }

    /**
     * @param string $target
     * @param string $action
     * @param array  $data
     *
     * @return array
     */
    protected function dispatch(string $target, string $action, array $data = []): array
    {
        return $this
            ->getInjection('eventManager')
            ->dispatch($target, $action, new Event($data));
    }
}

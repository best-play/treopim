/*
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

Espo.define('pim:views/category/record/panels/category-images', ['views/record/panels/relationship', 'views/record/panels/bottom', 'search-manager'],
    (Dep, BottomPanel, SearchManager) => Dep.extend({

        boolFilterData: {
            notLinkedWithCategory() {
                return this.model.id;
            }
        },

        setup() {
            let bottomPanel = new BottomPanel();
            bottomPanel.setup.call(this);

            this.link = this.link || this.defs.link || this.panelName;

            if (!this.scope && !(this.link in this.model.defs.links)) {
                throw new Error('Link \'' + this.link + '\' is not defined in model \'' + this.model.name + '\'');
            }
            this.title = this.title || this.translate(this.link, 'links', this.model.name);
            this.scope = this.scope || this.model.defs.links[this.link].entity;

            if (!this.getConfig().get('scopeColorsDisabled')) {
                var iconHtml = this.getHelper().getScopeColorIconHtml(this.scope);
                if (iconHtml) {
                    if (this.defs.label) {
                        this.titleHtml = iconHtml + this.translate(this.defs.label, 'labels', this.scope);
                    } else {
                        this.titleHtml = iconHtml + this.title;
                    }
                }
            }

            var url = this.url || this.model.name + '/' + this.model.id + '/' + this.link;

            if (!this.readOnly && !this.defs.readOnly) {
                if (!('create' in this.defs)) {
                    this.defs.create = true;
                }
                if (!('select' in this.defs)) {
                    this.defs.select = true;
                }
            }

            this.filterList = this.defs.filterList || this.filterList || null;

            if (this.filterList && this.filterList.length) {
                this.filter = this.getStoredFilter();
            }

            if (this.defs.create) {
                if (this.getAcl().check(this.scope, 'create') && !~['User', 'Team'].indexOf()) {
                    this.buttonList.push({
                        title: 'Create',
                        action: this.defs.createAction || 'createRelated',
                        link: this.link,
                        acl: 'create',
                        aclScope: this.scope,
                        html: '<span class="fas fa-plus"></span>',
                        data: {
                            link: this.link,
                            layout: this.defs.detailLayout
                        }
                    });
                }
            }

            if (this.defs.select) {
                var data = {link: this.link};
                if (this.defs.selectPrimaryFilterName) {
                    data.primaryFilterName = this.defs.selectPrimaryFilterName;
                }
                if (this.defs.selectBoolFilterList) {
                    data.boolFilterList = this.defs.selectBoolFilterList;
                }
                data.boolFilterListCallback = 'getSelectBoolFilterList';
                data.boolFilterDataCallback = 'getSelectBoolFilterData';
                data.afterSelectCallback = 'setScopeAfterSelect';

                this.actionList.unshift({
                    label: 'Select',
                    action: this.defs.selectAction || 'selectRelated',
                    data: data,
                    acl: 'edit',
                    aclScope: this.model.name
                });
            }

            this.setupActions();

            var layoutName = 'listSmall';
            var listLayout = null;
            var layout = this.defs.layout || null;
            if (layout) {
                if (typeof layout == 'string') {
                    layoutName = layout;
                } else {
                    layoutName = 'listRelationshipCustom';
                    listLayout = layout;
                }
            }
            var sortBy = this.defs.sortBy || null;
            var asc = this.defs.asc || null;

            this.wait(true);
            this.getCollectionFactory().create(this.scope, function (collection) {
                collection.maxSize = this.defs.recordsPerPage || this.getConfig().get('recordsPerPageSmall') || 5;

                if (this.defs.filters) {
                    var searchManager = new SearchManager(collection, 'listRelationship', false, this.getDateTime());
                    searchManager.setAdvanced(this.defs.filters);
                    collection.where = searchManager.getWhere();
                }

                collection.url = collection.urlRoot = url;
                if (sortBy) {
                    collection.sortBy = sortBy;
                }
                if (asc) {
                    collection.asc = asc;
                }
                this.collection = collection;

                this.setFilter(this.filter);

                if (this.fetchOnModelAfterRelate) {
                    this.listenTo(this.model, 'after:relate', function () {
                        collection.fetch();
                    }, this);
                }

                var viewName = this.defs.recordListView || this.getMetadata().get('clientDefs.' + this.scope + '.recordViews.list') || 'Record.List';

                this.listenTo(this.collection, 'listSorted', () => {
                    this.model.trigger('updateCategoryImage');
                });

                this.listenTo(this.model, 'after:relate after:unrelate', (link) => {
                    if (link === this.link) {
                        this.model.trigger('updateCategoryImage');
                    }
                });

                this.once('after:render', function () {
                    collection.once('sync', function () {
                        this.createView('list', viewName, {
                            collection: collection,
                            layoutName: layoutName,
                            listLayout: listLayout,
                            checkboxes: false,
                            rowActionsView: this.defs.readOnly ? false : (this.defs.rowActionsView || this.rowActionsView),
                            buttonsDisabled: true,
                            el: this.options.el + ' .list-container',
                            dragableListRows: true,
                            listRowsOrderSaveUrl: `CategoryImage/${this.getParentView().model.id}/sortOrder`
                        }, function (view) {
                            view.render();
                        });
                    }, this);
                    collection.fetch();
                }, this);

                this.wait(false);
            }, this);

            this.setupFilterActions();
        },

        getSelectBoolFilterList() {
            return this.defs.selectBoolFilterList || null
        },

        getSelectBoolFilterData(boolFilterList) {
            let data = {};
            if (Array.isArray(boolFilterList)) {
                boolFilterList.forEach(item => {
                    if (this.boolFilterData && typeof this.boolFilterData[item] === 'function') {
                        data[item] = this.boolFilterData[item].call(this);
                    }
                });
            }
            return data;
        },

        actionCreateRelatedCategoryImage(data) {
            data = data || {};

            var link = data.link;
            var layoutName = data.layout || null;
            var scope = this.model.defs['links'][link].entity;
            var foreignLink = this.model.defs['links'][link].foreign;

            var attributes = {};

            this.notify('Loading...');

            var viewName = this.defs.modalEditView
                || this.getMetadata().get('clientDefs.' + scope + '.modalViews.edit')
                || 'views/modals/edit';

            this.createView('quickCreate', viewName, {
                scope: scope,
                relate: {
                    model: this.model,
                    link: foreignLink,
                },
                attributes: attributes,
                layoutName: layoutName,
                categoryId: this.model.id
            }, function (view) {
                view.render();
                view.notify(false);
                this.listenToOnce(view, 'after:save', function () {
                    this.actionRefresh();
                    this.model.trigger('after:relate', this.link);
                }, this);
            }.bind(this));
        },

        setScopeAfterSelect(selectObj) {
            let data = {
                ids: selectObj.map(item => item.id)
            };
            this.ajaxPostRequest(`${this.model.name}/${this.model.id}/${this.link}`, data).then(() => {
                Promise.all(selectObj.map(item => {
                    return new Promise(resolve => {
                        item.fetch().then(() => {
                            let categoriesColumns = Espo.Utils.cloneDeep(item.get('categoriesColumns')) || {};
                            categoriesColumns[this.model.id] = {scope: 'Global'};
                            this.ajaxPutRequest(`${item.name}/${item.id}`, {categoriesColumns: categoriesColumns}).then(() => resolve());
                        })
                    });
                })).then(() => {
                    this.actionRefresh();
                    this.notify('Linked', 'success');
                    this.model.trigger('after:relate', this.link);
                });
            });
        },

        actionUnlinkRelated: function (data) {
            var id = data.id;

            this.confirm({
                message: this.translate('unlinkRecordConfirmation', 'messages'),
                confirmText: this.translate('Unlink')
            }, function () {
                var model = this.collection.get(id);
                this.notify('Unlinking...');
                $.ajax({
                    url: this.collection.url,
                    type: 'DELETE',
                    data: JSON.stringify({
                        id: id
                    }),
                    contentType: 'application/json',
                    success: function () {
                        this.notify('Unlinked', 'success');
                        this.collection.fetch();
                        this.model.trigger('after:unrelate', this.link);
                    }.bind(this),
                    error: function () {
                        this.notify('Error occurred', 'error');
                    }.bind(this),
                });
            }, this);
        },

        actionRemoveRelated: function (data) {
            var id = data.id;

            this.confirm({
                message: this.translate('removeRecordConfirmation', 'messages'),
                confirmText: this.translate('Remove')
            }, function () {
                var model = this.collection.get(id);
                this.notify('Removing...');
                model.destroy({
                    success: function () {
                        this.notify('Removed', 'success');
                        this.collection.fetch();
                        this.model.trigger('after:unrelate', this.link);
                    }.bind(this),
                });
            }, this);
        },
    })
);

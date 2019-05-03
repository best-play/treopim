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

Espo.define('pim:views/product/record/catalog-tree-panel/category-tree', 'view',
    Dep => Dep.extend({

        categoryTrees: [],

        template: 'pim:product/record/catalog-tree-panel/category-tree',

        events: {
            'show.bs.collapse div.panel-collapse.collapse[class^="catalog-"]': function (e) {
                e.stopPropagation();
                $(e.currentTarget).prev('button.catalog-link').find('span.caret').addClass('caret-up');
                this.$el.parent().find(`.panel-collapse.collapse[class^="catalog-"].in`).collapse('hide');
            },
            'hide.bs.collapse div.panel-collapse.collapse[class^="catalog-"]': function (e) {
                e.stopPropagation();
                $(e.currentTarget).prev('button.catalog-link').find('span.caret').removeClass('caret-up');
            },
            'show.bs.collapse div.panel-collapse.collapse[class^="category-"]': function (e) {
                e.stopPropagation();
                this.unfold($(e.currentTarget).data('id'));
            },
            'hide.bs.collapse div.panel-collapse.collapse[class^="category-"]': function (e) {
                e.stopPropagation();
                this.fold($(e.currentTarget).data('id'));
            },
            'click button.category.child-category': function (e) {
                this.selectCategory($(e.currentTarget).data('id'));
            }
        },
        
        data() {
            return {
                catalog: this.options.catalog,
                rootCategoriesList: this.getRootCategoriesList(),
                hash: this.getRandomHash()
            }
        },

        setup() {
            this.categories = this.options.categories || [];
            this.catalog = this.options.catalog;

            let rootCategoriesIds = [];
            this.categories.forEach(category => {
                if (category.categoryParentId) {
                    rootCategoriesIds.push(category.categoryParentId);
                }
            });
            this.rootCategories = this.categories.filter(category => {
                return rootCategoriesIds.includes(category.id) && (this.catalog.categoriesIds || []).includes(category.id);
            });
        },

        selectCategory(id) {
            let category = this.categoryTrees.find(item => item.id === id);
            category.catalogId = this.catalog.id;
            this.trigger('category-tree-select', category);
        },

        getRootCategoriesList() {
            let arr = [];
            this.rootCategories.forEach(category => {
                let hasChildren = this.categories.some(item => item.categoryParentId === category.id);
                arr.push({
                    id: category.id,
                    html: hasChildren ? this.getParentHtml(category) : this.getChildHtml(category)
                });
            });
            return arr;
        },

        getParentHtml(category) {
            let hash = this.getRandomHash();
            return `
                <li data-id="${category.id}" class="list-group-item child">
                    <button class="btn btn-link category category-icons" data-toggle="collapse" data-target=".category-${hash}" data-id="${category.id}" data-name="${category.name}">
                        <span class="fas fa-chevron-right"></span>
                        <span class="fas fa-chevron-down hidden"></span>
                    </button>
                    <button class="btn btn-link category child-category" data-id="${category.id}">
                        ${category.name}
                    </button>
                    <div class="category-${hash} panel-collapse collapse" data-id="${category.id}">
                        <ul class="list-group list-group-tree"></ul>
                    </div>
                </li>`;
        },

        getChildHtml(category) {
            return `
                <li data-id="${category.id}" class="list-group-item child">
                    <button class="btn btn-link category child-category" data-id="${category.id}" data-name="${category.name}">
                        ${category.name}
                    </button>
                </li>`;
        },

        getRandomHash() {
            return Math.floor((1 + Math.random()) * 0x100000000)
                .toString(16)
                .substring(1);
        },

        getFullEntity(url, params, callback, container) {
            if (url) {
                container = container || [];

                let options = params || {};
                options.maxSize = options.maxSize || 200;
                options.offset = options.offset || 0;

                this.ajaxGetRequest(url, options).then(response => {
                    container = container.concat(response.list || []);
                    options.offset = container.length;
                    if (response.total > container.length || response.total === -1) {
                        this.getFullEntity(url, options, callback, container);
                    } else {
                        callback(container);
                    }
                });
            }
        },

        fold(id) {
            let button = this.$el.find(`button.category-icons[data-id="${id}"]`);
            button.find('span.fa-chevron-right').removeClass('hidden');
            button.find('span.fa-chevron-down').addClass('hidden');
        },

        unfold(id) {
            this.setupCategoryTree(id, () => {
                let button = this.$el.find(`button.category-icons[data-id="${id}"]`);
                button.find('span.fa-chevron-right').addClass('hidden');
                button.find('span.fa-chevron-down').removeClass('hidden');
            });
        },

        setupCategoryTree(id, callback) {
            let category = this.categoryTrees.find(item => item.id === id);
            if (!category) {
                this.getTreeCategories(id, categories => {
                    category = this.buildCategoryTree(id, categories);
                    this.buildCategoryHtml(id, category);
                    callback();
                });
            } else {
                this.buildCategoryHtml(id, category);
                callback();
            }
        },

        getTreeCategories(id, callback) {
            this.getFullEntity('Category', {
                select: 'name,categoryParentId,categoryRoute',
                where: [
                    {
                        type: 'contains',
                        attribute: 'categoryRoute',
                        value: id
                    }
                ]
            }, categories => {
                callback(categories);
            });
        },

        buildCategoryTree(id, categories) {
            let root = this.rootCategories.find(item => item.id === id);

            let setChilds = (category, categories) => {
                let childs = categories.filter(item => item.categoryParentId === category.id);
                if (childs.length) {
                    childs.forEach(child => setChilds(child, categories));
                }
                category.childs = childs;
                if (!this.categoryTrees.some(item => item.id === category.id)) {
                    this.categoryTrees.push(category);
                }
            };
            setChilds(root, categories);

            return root;
        },

        buildCategoryHtml(id, category) {
            if (!category.htmlLoaded) {
                let html = '';
                category.childs.forEach(category => {
                    html += category.childs.length ? this.getParentHtml(category) : this.getChildHtml(category);
                });
                let button = this.$el.find(`button.category-icons[data-id="${id}"]`);
                let listEl = button.parent().find(`.panel-collapse[data-id="${id}"] .list-group-tree`);
                listEl.append(html);
                category.htmlLoaded = true;
            }
        },

    })
);
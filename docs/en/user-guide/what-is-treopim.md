# What is TreoPIM

TreoPIM is a single page application (SPA) with an API-centric architecture and flexible data model based on entities, entity attributes and relations of all kinds among them. TreoPIM allows you to gather and store all your product content in one place, enrich it and spread it to several channels like own online shop, Amazon, eBay, online shops of your distributors, on a tablet or mobile application. TreoPIM will help your to structure and organize all you flexible data and get rid of the Excel mess.

TreoPIM is developed and supported by [TreoLabs GmbH](https://treolabs.com).

## Main Definitions

[**Locale**](https://treopim.com/store/multi-language-and-local-fields) – a combination of a language (i.e. German, English) and a country/region (i.e. Germany, United Kingdom, the U.S.A.) preferred to be used in the interface. For instance, German German is marked as "de_DE", Austrian German is marked as "de_AT", UK English is marked as "en_GB" and so on. You can use one or more locales in the system. 

This functionality is enabled when the  **"Multi-languages"** module is installed to your system. Please, see the details in the [TreoPIM store](https://treopim.com/store/multi-languages).

**Attribute** – a product's property. Each product can be characterized by one or several attributes. There are over 20 attribute types available for you in TreoPIM. Some attribute types allow you to store unique attribute values per locale for your products. Products can have specific attribute values for channels.

**Attribute group** – a way to categorize the attributes. Different attributes of the same nature can be assigned to the same attribute group. You can have multiple attribute groups in TreoPIM.

**Brand** – a brand of the product or the name of its manufacturer. Brands create additional possibility to categorize the products.

**Category** – a way to classify and group the products by certain criteria. A category can have only one parent category. A category without a parent category is called a root category. A root category starts a category tree. In TreoPIM you can have multiple category trees. A category tree can have unlimited levels. A category without any child category is called a leaf category.

**Channel** – a destination point for your product information, for example, your online shop, mobile app or print catalog. A channel defines a set of product information, which can be synced with the third-party systems as well as exported in certain formats.

**Product Family** – a means of standardizing your product information that helps you group similar products, which use similar or same production processes, have similar physical characteristics, and may share customer segments, distribution channels, pricing methods, promotional campaigns, and other elements of the marketing mix. In TreoPIM product families are use to define a set of attributes that are shared by products belonging to a certain family, to describe the characteristics of these products. A product can belong to only one product family.

**Product** – an item in physical, virtual or cyber form as well as a service offered for sale. Each product is made at a cost and is sold at a price. Each product has a certain type (e.g. simple, configurable, etc.) and can be assigned to a certain product family, which will define, what attributes are to be set for this product. Product can be assigned to several categories, be of a certain brand, described in several languages and be prepared for selling via different channels. A product can be in association of a certain type with some other products.

**Product Type** – a kind of product definition that specifies in which way the product should be described. Products of different types may have different or additional UIs, to define all specific product options needed..

**Association** – a means of regulating types of relationships between the products. In TreoPIM associations can be one-sided, when Product A is associated with Product B and not vice versa, or two-sided, when both Product A and Product B are associated with each other.

**Module** – an extension or module of the TreoPIM system aimed at expanding or modifying its functionality to such an extend that all customer needs are met. TreoPIM is an extremely flexible system, so it is possible to change almost everything. "Connector" is a module, which is implemented to interact with the third-party systems and exchange the data between them. The API of TreoPIM or API of a third-party system can be used for it.

**Dashboard** – a collection of data displayed in a graphical or table layout as widgets. Dashboards allow users to have all the important information grouped by a certain type or nature in one single place. Every user can have several dashboards.

## Main Concepts

### Concept of Entity

An entity is any singular, identifiable and separate object or unit of data that can be classified and have stated relationships to other entities. The entities are used to model and manage business data. For example, in TreoPIM the following entities are used: Products, Categories, Product Images, Channels, Attributes, Attribute Groups, Product Families, etc. 

An entity has a set of attributes, and each attribute represents a data item of a particular type. For example, the "Channel" entity has the following attributes: Name, Activity State, Type, Currencies, Description and some other system attributes. Conceptually, an entity is like a database table, and the entity attributes are the table columns. New information in the entity is stored as an entity record - this is like adding a row in a database table.

The entities are divided into three categories: system (hidden from Administrator), business and custom (both available for Administrator). Administrators, working with your business data, can create new custom entities or modify existing business or custom entities, set relations between the entities. In TreoPIM it is possible to set business and custom entities and attributes to be either customisable or non-customisable. You can modify a customisable entity by renaming it, adding new attributes, deleting or changing attributes. You cannot modify a non-customisable entity. Developers can change everything.

### Concept of Record Deletion

All records always remain physically present in the database. The state of the record, whether it is deleted or not, is defined by the  "isDeleted" system attribute. All records where the "isDeleted" attribute is true are not visible for the user, as if they were deleted.

Only Developer can recover the deleted records.

Please note, if changes in the data structure occur, no data for removed fields can be recovered from the database any more.

### Concept of Activeness

It is possible to define for each entity in the system whether their records can be activated and deactivated.

Deactivated records cannot be in relation to any other entities. They are available for search only in the UI of that very entity; in all other interfaces they are not visible.

### Concept of Data Auditing

Each entity field can be set to be audited or not. When this option is activated, all the changes made to the field value for a certain record are logged and shown in the activity stream as follows: old value, new value, who has made the change and when. 

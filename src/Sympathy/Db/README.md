Sympathy DB: Object-oriented CRUD for Doctrine DBAL
===================================================

The Sympathy Model and Database Access Object (DAO) classes encapsulate Doctrine DBAL to provide high-performance, object-oriented CRUD (create, read, update, delete) functionality for relational databases.

See also: [Sympathy Form - A value container with built-in whitelist validation](https://github.com/lastzero/sympathy/tree/master/src/Sympathy/Form)

![Architecture](https://www.lucidchart.com/publicSegments/view/5461d17e-f5a8-4166-9e43-47200a00dd77/image.png)

*Note: This is not an ORM (object-relational mapping) framework and there are no requirements to the underlying database structure.*

Data Access Objects
-------------------
DAOs directly deal with **database tables** and **raw SQL**, if needed. `Sympathy\Db\Dao` is suited to implement custom methods using raw SQL. All DAOs expose the following public methods by default:
- `factory($name)`: Returns a new DAO instance
- `beginTransaction()`: Start a database transaction
- `commit()`: Commit a database transaction
- `rollBack()`: Roll back a database transaction

In addition, `Sympathy\Db\Entity` offers many powerful methods to easily deal with database table rows:
- `setData(array $data)`: Set raw data (changes can not be detected, e.g. when calling update())
- `setValues(array $data)`: Set multiple values
- `setDefinedValues(array $data)`: Set values that exist in the table schema only (slower than setValues())
- `getValues()`: Returns all values as array
- `find($id)`: Find a row by primary key
- `reload()`: Reload row values from database
- `getValues()`: Returns all values as associative array
- `exists($id)`: Returns true, if a row with the given primary key exists
- `insert()`: Insert a new row
- `update()`: Updates changed values in the database
- `delete()`: Delete entity from database
- `getId()`: Returns the ID of the currently loaded record (throws exception, if empty)
- `hasId()`: Returns true, if the DAO instance has an ID assigned (primary key)
- `setId($id)`: Set primary key
- `findAll(array $cond = array(), $wrapResult = true)`: Returns all instances that match $cond (use search() or searchAll(), if you want to limit or sort the result set)
- `search(array $params)`: Powerful alternative to findAll() to search the database incl. count, offset and order
- `wrapAll(array $rows)`: Create and return a new DAO for each array element
- `updateRelationTable($relationTable, $primaryKeyName, $foreignKeyName, array $existing, array $updated)`: Helper function to update n-to-m relationship tables
- `hasTimestampEnabled()`: Returns true, if this DAO automatically adds timestamps when creating and updating rows
- `findList($colName, $order = '', $where = '', $indexName = '')`: Returns a key/value array (list) of all matching rows
- `getTableName()`: Returns the name of the underlying database table
- `getPrimaryKeyName()`: Returns the name of the primary key column (throws an exception, if primary key is an array)

search() accepts the following optional parameters to limit, filter and sort search results:
- `table`: Table name
- `table_alias`: Alias name for "table" (table reference for join and join_left)
- `cond`: Filters as associative array (key = value)
- `count`: Maximum number of results (integer)
- `offset`: Result offset (integer)
- `join`: List of joined tables incl join condition e.g. `array(array('u', 'phonenumbers', 'p', 'u.id = p.user_id'))`, see Doctrine DBAL manual
- `left_join`: See join
- `columns`: List of columns (array)
- `order`: Sort order (if not false)
- `group`: Group by (if not false)
- `wrap`: If false, raw arrays are returned instead of DAO instances
- `ids_only`: Return primary key values only
- `sql_filter`: Raw SQL filter (WHERE)
- `id_filter`: If not empty, limit result to this list of primary key IDs

DAO entities are configured using protected class properties:

    protected $_tableName = ''; // Database table name
    protected $_primaryKey = 'id'; // Name or array of primary key(s)
    protected $_fieldMap = array(); // 'db_column' => 'object_property'
    protected $_formatMap = array(); // 'db_column' => Format::TYPE
    protected $_valueMap = array(); // 'object_property' => 'db_column'
    protected $_timestampEnabled = false; // Automatically update timestamps?
    protected $_timestampCreatedCol = 'created';
    protected $_timestampUpdatedCol = 'updated';

Possible values for $_formatMap are defined as constants in `Sympathy\Db\Format`:

    const NONE = '';
    const INT = 'int';
    const FLOAT = 'float';
    const STRING = 'string';
    const ALPHANUMERIC = 'alphanumeric';
    const SERIALIZED = 'serialized';
    const JSON = 'json';
    const BOOL = 'bool';
    const TIME = 'H:i:s';
    const DATE = 'Y-m-d';
    const DATETIME = 'Y-m-d H:i:s';
    const TIMESTAMP = 'U';

Example:
    
    <?php
    
    namespace App\Dao;
    
    use Sympathy\Db\Entity;
    
    class UserDao extends Entity
    {
        protected $_factoryNamespace = 'App\\Dao';
        protected $_tableName = 'users';
        protected $_primaryKey = 'user_id';
        protected $_timestampEnabled = true;
    }

Models
------
**Models** (also called "Business Models" or "Business Objects") are logically located between **Controllers** - which render views and validate user input - and **Data Access Objects** (DAOs), that are low-level interfaces to a storage backend or Web service.

Public interfaces of models are high-level and should reflect all use cases within their domain. There are a number of standard use-cases that are pre-implemented in the base class `Sympathy\Db\Model`:
- `factory($name = '', Dao $dao = null)`: Create a new model instance
- `find($id)`: Find a record by primary key
- `reload()`: Reload values from database
- `findAll(array $cond = array(), $wrapResult = true)`: Find multiple records; if `$wrapResult` is false, plain DAOs are returned instead of model instances
- `search(array $cond, array $options = array())`: Perform a search ($options can contain count, offset and/or sort order; the return value array also contains count, offset, sort order plus the total number of results; see DAO documentation)
- `searchAll(array $cond = array(), $order = false)`: Simple version of search(), similar to findAll()
- `searchOne(array $cond = array())`: Search a single record; throws an exception if 0 or more than one record are found
- `searchIds(array $cond, array $options = array())`: Returns an array of matching primary keys for the given search condition
- `getModelName()`: Returns the model name without prefix and postfix
- `getId()`: Returns the ID of the currently loaded record (throws exception, if empty)
- `hasId()`: Returns true, if the model instance has an ID assigned (primary key)
- `getValues()`: Returns all model properties as associative array
- `getEntityTitle()`: Returns the common name of this entity
- `isDeletable()`: Returns true, if the model instance can be deleted with delete()
- `isUpdatable()`: Returns true, if the model instance can be updated with update($values)
- `isCreatable()`: Returns true, if new entities can be created in the database with create($values)
- `batchEdit(array $ids, array $properties)`: Update data for multiple records
- `getTableName()`: Returns the name of the associated main database table
- `hasTimestampEnabled()`: Returns true, if timestamps are enabled for the associated DAO
- `delete()`: Permanently delete the entity record from the database
- `create(array $values)`: Create a new record using the values provided
- `update(array $values)`: Update model instance database record; before assigning multiple values to a model instance, data should be validated using a form class

**How much validation should be implemented within a model?** Wherever invalid data can lead to security issues or major inconsistencies, some core validation rules must be implemented in the model layer. Model exception messages usually don’t require translation (in multilingual applications), since invalid values should be recognized beforehand by a form class. If you expect certain exceptions, you should catch and handle them in your controllers.

Similar to DAOs, models are configured using protected class properties:

    protected $_daoName = ''; // Primary DAO name (no prefix/postfix)
    protected $_factoryNamespace = '';
    protected $_factoryPostfix = 'Model';
    protected $_daoFactoryNamespace = '';
    protected $_daoFactoryPostfix = 'Dao';

Example:

    <?php
    
    namespace App\Model;
    
    use Sympathy\Db\Model;
    
    class User extends Model 
    {
      protected $_daoName = ‘User’;
      protected $_factoryNamespace = 'App\\Model';
      protected $_factoryPostfix = '';
      protected $_daoFactoryNamespace = 'App\\Dao';
      
      public function delete() 
      {
        $dao = $this->getDao();
        $dao->is_deleted = 1;
        $dao->update();
      }
    
      public function undelete() 
      {
        $dao = $this->getDao();
        $dao->is_deleted = 0;
        $dao->update();
      }
    
      public function search(array $cond, array $options = array()) 
      {
        $cond[‘is_deleted’] = 0;
        return parent::search($cond, $options);
      }
      
      public function getValues()
      {
        $result = parent::getValues();
        unset($result['password']);
        return $result;
      }
    }
    
Usage
-----

    <?php
    
    namespace App\Rest;
    
    use Symfony\Component\HttpFoundation\Request;
    use App\Exception\FormInvalidException;
    use App\Form\UserForm;
    use App\Model\User;
    
    class UserController
    {
        protected $user;
        protected $form;

        public function __construct(User $user, UserForm $form)
        {
            $this->user = $user;
            $this->form = $form;
        }

        public function cgetAction()
        {
            $users = $this->user->findAll();
            $result = array();
    
            foreach($users as $user) {
                $result[] = $user->getValues();
            }
    
            return $result;
        }
    
        public function getAction($id)
        {
            return $this->user->find($id)->getValues();
        }
    
        public function deleteAction($id)
        {
            return $this->user->find($id)->delete();
        }
    
        public function putAction($id, Request $request)
        {
            $this->user->find($id);
            $this->form->setDefinedWritableValues($request->request->all())->validate();
    
            if($this->form->hasErrors()) {
                throw new FormInvalidException($this->form->getFirstError());
            } else {
                $this->user->update($this->form->getValues());
            }
    
            return $this->user->getValues();
        }
    
        public function postAction(Request $request)
        {
            $this->form->setDefinedWritableValues($request->request->all())->validate();
    
            if($this->form->hasErrors()) {
                throw new FormInvalidException($this->form->getFirstError());
            } else {
                $this->user->create($this->form->getValues());
            }
    
            return $this->user->getValues();
        }
    }

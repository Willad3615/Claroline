<?php
namespace Claroline\DummyPluginBundle\Migrations;

use Claroline\PluginBundle\Migration\PluginMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20110916130400 extends PluginMigration
{
    
    public function up(Schema $schema)
    {
        $table = $schema->createTable($this->prefix() . '_stuffs');
        $table->addColumn(
            'id',
            'integer',
            array(
                'notnull' => true,
                'autoincrement' => true,
            )
        );
        
        $table->addColumn(
            'name', 
            'string', 
            array(
                'length' => 50
            )
        );
        
        $table->addUniqueIndex(array('id'));
        
    }

    public function down(Schema $schema)
    {
        $schema->dropTable($this->prefix() . '_stuffs');
    }


}
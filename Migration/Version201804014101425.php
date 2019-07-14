<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Yaml\Yaml;


class Version201804014101425 extends AbstractMigration
{

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->createPlgSimpleNemPayInfo($schema);
        $this->createPlgSimpleNemPayOrder($schema);
        $this->createPlgSimpleNemPayHistory($schema);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $app = new \Eccube\Application();
        $app->initialize();
        $app->initializePlugin();
        $app->boot();

        $this->deleteFromDtbPayment();
        // this down() migration is auto-generated, please modify it to your needs
        $schema->dropTable('plg_simple_nempay_info');
        $schema->dropTable('plg_simple_nempay_order');
        $schema->dropTable('plg_simple_nempay_history');
    }

    public function postUp(Schema $schema)
    {
        $app = new \Eccube\Application();
        $app->initialize();
        $app->initializePlugin();
        $app->boot();

        $datetime = date('Y-m-d H:i:s');

        // DBタイプ取得
        $config_file = __DIR__ . '/../../../config/eccube/database.yml';
        $config = Yaml::parse(file_get_contents($config_file));

        // rank取得
        $select = "SELECT max(rank)+1 FROM dtb_payment";
        $rank = $this->connection->fetchColumn($select);

        // 支払い方法「かんたんNEM決済」追加
        $payment_id = '';
        if ($config['database']['driver'] == 'pdo_mysql') {
            $insert = "INSERT INTO dtb_payment(creator_id, payment_method, charge, rule_max, rank, create_date, update_date, rule_min)
                        VALUES (1, 'かんたんNEM決済', 0, null, $rank, '$datetime', '$datetime', null);";
            $this->connection->executeUpdate($insert);

            // 「かんたんNEM決済」のpayment_id取得
            $select = "SELECT max(payment_id) FROM dtb_payment WHERE payment_method = 'かんたんNEM決済'";
            $payment_id = $this->connection->fetchColumn($select);
        } else {
            $nextval = "SELECT nextval('dtb_payment_payment_id_seq')";
            $payment_id = $this->connection->fetchColumn($nextval);
            $insert = "INSERT INTO dtb_payment(payment_id, creator_id, payment_method, charge, rule_max, rank, create_date, update_date, rule_min)
                        VALUES ($payment_id, 1, 'かんたんNEM決済', 0, null, $rank, '$datetime', '$datetime', 0);";
            $this->connection->executeUpdate($insert);
        }

        // プラグイン情報初期セット
        $insert = "INSERT INTO plg_simple_nempay_info(id, code, name, payment_id, create_date, update_date)
                    VALUES (1, 'SimpleNemPay', 'SimpleNemPay', $payment_id, '$datetime', '$datetime');";
        $this->connection->executeUpdate($insert);
    }

    /**
     * create table plg_simple_nempay_info
     *
     * @param Schema $schema
     */
    public function createPlgSimpleNemPayInfo(Schema $schema)
    {
        $table = $schema->createTable('plg_simple_nempay_info');

        $table->addColumn('id', 'integer', array(
            'autoincrement' => true,
        ));
        $table->addColumn('code', 'text', array(
            'notnull' => true,
        ));
        $table->addColumn('name', 'text', array(
            'notnull' => true,
        ));
        $table->addColumn('setting_data', 'text', array(
            'notnull' => false,
        ));
        $table->addColumn('payment_id', 'integer', array(
            'notnull' => true,
        ));
        $table->addColumn('del_flg', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 0,
        ));
        $table->addColumn('create_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));
        $table->addColumn('update_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->setPrimaryKey(array('id'));
        $table->addForeignKeyConstraint('dtb_payment', array('payment_id'), array('payment_id'));

    }

    /**
     * create table plg_simple_nempay_order
     *
     * @param Schema $schema
     */
    public function createPlgSimpleNemPayOrder(Schema $schema)
    {
        $table = $schema->createTable('plg_simple_nempay_order');

        $table->addColumn('nem_order_id', 'integer', array(
            'autoincrement' => true,
        ));
        $table->addColumn('order_id', 'integer', array(
            'notnull' => false,
        ));
        $table->addColumn('rate', 'float', array(
            'notnull' => false,
        ));
        $table->addColumn('payment_amount', 'float', array(
            'notnull' => false,
        ));
        $table->addColumn('remittance_amount', 'float', array(
            'notnull' => false,
        ));
        $table->addColumn('payment_info', 'text', array(
            'notnull' => false,
        ));

        $table->setPrimaryKey(array('nem_order_id'));

        $table->addForeignKeyConstraint('dtb_order', array('order_id'), array('order_id'));
    }

    /**
     * create table plg_simple_nempay_history
     *
     * @param Schema $schema
     */
    public function createPlgSimpleNemPayHistory(Schema $schema)
    {
        $table = $schema->createTable('plg_simple_nempay_history');

        $table->addColumn('nem_history_id', 'integer', array(
            'autoincrement' => true,
        ));
        $table->addColumn('nem_order_id', 'integer', array(
            'notnull' => false,
        ));
        $table->addColumn('transaction_id', 'text', array(
            'notnull' => false,
        ));
        $table->addColumn('amount', 'float', array(
            'notnull' => false,
        ));

        $table->setPrimaryKey(array('nem_history_id'));

        $table->addForeignKeyConstraint('plg_simple_nempay_order', array('nem_order_id'), array('nem_order_id'));
    }

    public function deleteFromDtbPayment()
    {
        // 「かんたんNEM決済」のpayment_idを取得
        $select = "SELECT payment_id FROM plg_simple_nempay_info";
        $payment_id = $this->connection->fetchColumn($select);

        $update = "UPDATE dtb_payment SET del_flg = 1 WHERE payment_id = $payment_id";
        $this->connection->executeUpdate($update);

        $table = "dtb_payment_option";
        $where = array("payment_id" => $payment_id);
        $this->connection->delete($table, $where);
    }


    function getSimpleNemPayCode()
    {
        $config = \Eccube\Application::alias('config');

        return "";
    }
}

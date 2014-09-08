<?php

namespace Example\Migrations\TestAntiMattr\MongoDB;

use AntiMattr\MongoDB\Migrations\AbstractMigration;
use Doctrine\MongoDB\Database;

class Version20140822185742 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'First Version prepares Index';
    }

    public function up(Database $db)
    {
        $testA = $db->selectCollection('test_a');
        $this->analyze($testA);

        $testA->ensureIndex(array('actor' => -1));
    }

    public function down(Database $db)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }

    /**
	 * This preUp is not required
	 * I use it to demonstrate the analyzer
	 */
    public function preUp(Database $db)
    {
        $testA = $db->selectCollection('test_a');

        $testDocuments = array();

        for ($i = 0; $i < 100; $i++) {
            $testDocument = array();
            $testDocument['iteration'] = $i;
            $testDocument['actor'] = $this->generateRandomString();
            $testDocument['object'] = $this->generateRandomString();
            $testDocument['target'] = $this->generateRandomString();
            $testDocument['verb'] = $this->generateRandomString();
            $testDocuments[] = $testDocument;
        }

        $testA->batchInsert($testDocuments);
    }

    /**
	 * This postUp is not required
	 * I use it to demonstrate the analyzer
	 */
    public function postUp(Database $db)
    {
        $testA = $db->selectCollection('test_a');
        $testA->drop();
    }

    private function generateRandomString()
    {
        $length = rand(10, 50);
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomString;
    }
}

<?php

/**
 * @package Tests
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
 */

namespace Ox\Interop\Ftp\Tests\Unit;

use Ox\Core\CAppUI;
use Ox\Core\CMbException;
use Ox\Core\Contracts\Client\SFTPClientInterface;
use Ox\Interop\Eai\Resilience\ClientContext;
use Ox\Interop\Ftp\CSFTP;
use Ox\Interop\Eai\Client\Legacy\CFileSystemLegacy;
use Ox\Interop\Ftp\CSourceSFTP;
use Ox\Interop\Ftp\ResilienceSFTPClient;
use Ox\Tests\OxUnitTestCase;
use stdClass;

class CSFTPTest extends OxUnitTestCase
{
    /** @var CSourceSFTP */
    protected static $source;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $source                 = new CSourceSFTP();
        $source->loggable       = "0";
        $source->name           = 'test_TU_SFTP';
        $source->host           = 'localhost';
        $source->active         = 1;
        $source->role           = CAppUI::conf('instance_role');
        $source->retry_strategy = "1|5 5|60 10|120 20|";

        if ($msg = $source->store()) {
            throw new CMbException($msg);
        }

        self::$source = $source;
    }

    /**
     * @return CSFTP
     */
    public function testInit(): CSFTP
    {
        $sftp            = new CSFTP();
        $exchange_source = self::$source;
        $sftp->init($exchange_source);

        $this->assertInstanceOf(CSourceSFTP::class, $exchange_source);

        return $sftp;
    }

    /**
     * TestTruncate
     */
    public function testTruncate(): void
    {
        $text = new stdClass();
        $this->assertInstanceOf(stdClass::class, CSFTP::truncate($text));

        // length 100
        $text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labor.';

        $string = null;
        for ($i = 0; $i <= 10; $i++) {
            $string .= $text;
        }

        $string_truncated = CSFTP::truncate($string);

        $this->assertStringEndsWith('... [1100 bytes]', $string_truncated);

        $this->assertEquals(1024 + 13, strlen($string_truncated));
    }

    /**
     * @return void
     */
    public function testGenerateNameFile(): void
    {
        $res   = CSourceSFTP::generateFileName();
        $regex = "/(\d.+\_\d.+)/";
        $this->assertTrue((bool)preg_match($regex, $res));
    }

    public function testGetClient(): void
    {
        $source = $this->getMockBuilder(CSourceSFTP::class)->getMock();
        $client = $this->getMockBuilder(SFTPClientInterface::class)->getMock();
        $source->method('getClient')->willReturn($client);
        $this->assertInstanceOf(SFTPClientInterface::class, $client);
    }

    public function testGetClientCache(): void
    {
        $source = self::$source;
        $client = $source->getClient();
        $this->assertSame($client, $source->getClient());
    }

    public function testGetClientRetryable(): void
    {
        $source                 = self::$source;
        $source->retry_strategy = "1|5 5|60 10|120 20|";
        $client                 = $source->getClient();
        $this->assertInstanceOf(ResilienceSFTPClient::class, $client);
    }

    public function testGetClientOx(): void
    {
        $source                 = self::$source;
        $source->retry_strategy = "";
        $source->_client        = "";
        $client                 = $source->getClient();
        $this->assertInstanceOf(CSFTP::class, $client);
    }

    public function testOnBeforeRequestIsNotLoggable(): void
    {
        $source           = self::$source;
        $source->loggable = "0";
        $client           = $source->getClient();
        $client_context   = new ClientContext($client, $source);

        $source->_dispatcher->dispatch($client_context, $client::EVENT_BEFORE_REQUEST);

        $this->assertNotNull($source->_current_echange);
    }

    public function testOnBeforeRequest(): void
    {
        $source           = self::$source;
        $source->host     = "www.test.com";
        $source->_client  = "";
        $source->loggable = "1";
        $client           = $source->getClient();
        $client_context   = new ClientContext($client, $source);

        $source->_dispatcher->dispatch($client_context, $client::EVENT_BEFORE_REQUEST);

        $this->assertNotNull($source->_current_echange);

        $this->assertNotNull($source->_current_echange->date_echange);
        $this->assertIsString($source->_current_echange->date_echange);

        $this->assertNotNull($source->_current_echange->destinataire);
        $this->assertIsString($source->_current_echange->destinataire);

        $this->assertNotNull($source->_current_echange->source_id);
    }

    public function testOnAfterRequest(): void
    {
        $source       = self::$source;
        $source->host = "www.test.com";
        //$source->loggable = "1";
        $source->_client = "";
        $client          = $source->getClient();
        $client_context  = new ClientContext($client, $source);

        $source->_dispatcher->dispatch($client_context, $client::EVENT_BEFORE_REQUEST);
        $source->_dispatcher->dispatch($client_context, $client::EVENT_AFTER_REQUEST);

        $this->assertNotNull($source->_current_echange);

        $this->assertNotNull($source->_current_echange->date_echange);
        $this->assertIsString($source->_current_echange->date_echange);

        $this->assertNotNull($source->_current_echange->destinataire);
        $this->assertIsString($source->_current_echange->destinataire);

        $this->assertNotNull($source->_current_echange->source_id);

        $this->assertNotNull($source->_current_echange->response_time);
        $this->assertIsFloat($source->_current_echange->response_time);
        $this->assertGreaterThan(0, $source->_current_echange->response_time);

        $this->assertNotNull($source->_current_echange->response_datetime);

        if ($source->_current_echange->output !== null) {
            $this->assertIsString($source->_current_echange->output);
        } else {
            $this->assertNull($source->_current_echange->output);
        }
    }

    public function testOnException(): void
    {
        $source         = self::$source;
        $source->host   = "www.test.com";
        $client         = $source->getClient();
        $client_context = new ClientContext($client, $source);
        $client_context->setResponse("test methode onException");

        $source->_dispatcher->dispatch($client_context, $client::EVENT_BEFORE_REQUEST);
        $source->_dispatcher->dispatch($client_context, $client::EVENT_EXCEPTION);

        $this->assertNotNull($source->_current_echange);

        $this->assertNotNull($source->_current_echange->response_datetime);
        $this->assertIsString($source->_current_echange->response_datetime);

        $this->assertNotNull($source->_current_echange->output);
        $this->assertIsString($source->_current_echange->output);
    }
}

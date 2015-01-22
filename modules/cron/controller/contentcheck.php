<?php

use Cron\Etc\Controller;
use THCFrame\Events\Events as Event;
use THCFrame\Request\Request;

/**
 * 
 */
class Cron_Controller_ContentCheck extends Controller
{

    /**
     * Method check if sellable categories do or doesnt contain string 'za 1. den'
     * which should not be there
     * 
     * @before _cron
     */
    public function checkSellableCategory()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;
        $startTime = microtime(true);

        Event::fire('cron.log', array('success', 'Checking sellable categories content'));

        require_once APP_PATH . '/vendors/swiftmailer/swift_required.php';
        $transport = Swift_SmtpTransport::newInstance('smtp.ebola.cz', 465, 'ssl')
                ->setUsername('croncheck@agenturakarneval.cz')
                ->setPassword('AgKarCron-2015-');
        $mailer = Swift_Mailer::newInstance($transport);

        $categories = App_Model_Category::all(array('isSelable = ?' => true, 'active = ?' => true));

        if (null !== $categories) {
            $body = '';

            foreach ($categories as $category) {
                $requestUrl = 'http://' . $this->getServerHost() . '/kategorie/' . $category->getUrlKey() . '/';

                $request = new Request();
                $response = $request->request('get', $requestUrl);

                if (stripos($response, 'za 1. den') !== false) {
                    Event::fire('cron.log', array('fail', 'Sellable category ' . $category->getTitle() . ' contains string "za 1. den"'));

                    $body .= 'Byl objeven výskyt "za 1. den" v prodejné kategorii'
                            . ' <a href="' . $requestUrl . '">' . $category->getTitle() . '</a><br/><br/>';
                }
            }

            if ($body !== '') {
                $message = Swift_Message::newInstance()
                        ->setSubject('AgenturaKarneval sellable category content check')
                        ->setFrom('croncheck@agenturakarneval.cz')
                        ->setTo('hodan.tomas@gmail.com')
                        ->setBody($body, 'text/html');

                $mailer->send($message);
            }

            $time = microtime(true) - $startTime;
            Event::fire('cron.log', array('success', 'Checking sellable categories content finished in ' . $time . ' sec'));
        } else {
            Event::fire('cron.log', array('fail', 'No sellable categories found'));
        }

        exit;
    }

}

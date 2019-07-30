<?php

namespace Improntus\Moova\Controller\Adminhtml\Retiro;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Descargar
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Controller\Adminhtml\Retiro
 */
class Descargar extends \Magento\Backend\App\Action
{
    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\ResultFactory
     */
    protected $_resultRedirect;

    /**
     * @var \Improntus\Moova\Model\Moova
     */
    protected $_moova;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;

    /**
     * Descargar constructor.
     * @param Action\Context $context
     * @param \Improntus\Moova\Model\Moova $moova
     * @param ResultFactory $resultFactory
     * @param \Magento\Framework\Message\ManagerInterface $manager
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct
    (
        Action\Context $context,
        \Improntus\Moova\Model\Moova $moova,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Message\ManagerInterface $manager,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem
    )
    {
        $this->_moova = $moova;
        $this->_resultRedirect = $resultFactory;
        $this->messageManager = $manager;
        $this->_fileFactory = $fileFactory;
        $this->_filesystem = $filesystem;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        $moovaId = $request->getParam('moova_id');

        try
        {
            $shipment = $this->_moova->getShippingLabel($moovaId);

            if($shipment !== false)
            {
                $url = $shipment['label'];
                $mediapath = $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath() . 'moova/';
                $file = $mediapath . basename($url);

                if (!file_exists($mediapath) || !is_dir($mediapath))
                {
                    mkdir("{$mediapath}", 0775,true);
                }

                $fp = fopen($file, 'w');

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_FILE, $fp);

                $data = curl_exec($ch);

                curl_close($ch);
                fclose($fp);

                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename='.basename($file));
                header('Content-Transfer-Encoding: binary');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file));
                ob_clean();
                flush();
                readfile($file);
                exit;
            }
            else
                $this->messageManager->addErrorMessage(__('Se produjo un error al generar el envío MOOVA. Por favor intentelo nuevamente'));
        }
        catch (\Exception $e)
        {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $resultRedirect = $this->_resultRedirect->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}



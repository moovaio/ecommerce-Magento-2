<?php

namespace Improntus\Moova\Controller\Adminhtml\Retiro;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class Solicitar
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Controller\Adminhtml\Retiro
 */
class Solicitar extends \Magento\Backend\App\Action
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
     * Solicitar constructor.
     * @param Action\Context $context
     * @param \Improntus\Moova\Model\Moova $moova
     * @param ResultFactory $resultFactory
     * @param \Magento\Framework\Message\ManagerInterface $manager
     */
    public function __construct
    (
        Action\Context $context,
        \Improntus\Moova\Model\Moova $moova,
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        \Magento\Framework\Message\ManagerInterface $manager
    )
    {
        $this->_moova = $moova;
        $this->_resultRedirect = $resultFactory;
        $this->messageManager = $manager;

        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $request = $this->getRequest();
        $orderId = $request->getParam('order_id');

        try
        {
            $shipment = $this->_moova->doShipment($orderId);

            if($shipment)
                $this->messageManager->addSuccessMessage($shipment);
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



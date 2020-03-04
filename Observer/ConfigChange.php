<?php

/**
 * MagePrince
 * Copyright (C) 2020 Mageprince <info@mageprince.com>
 *
 * @package Prince_Faq
 * @copyright Copyright (c) 2020 Mageprince (http://www.mageprince.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author MagePrince <info@mageprince.com>
 */

namespace Prince\Faq\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Magento\Store\Model\StoreManagerInterface;

class ConfigChange implements ObserverInterface
{
    const REQUEST_PATH = 'faq';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var WriterInterface
     */
    private $configWriter;
    /**
     * @var UrlRewriteFactory
     */
    private $urlRewriteFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * ConfigChange constructor.
     * @param RequestInterface $request
     * @param WriterInterface $configWriter
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        RequestInterface $request,
        WriterInterface $configWriter,
        UrlRewriteFactory $urlRewriteFactory,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->request = $request;
        $this->configWriter = $configWriter;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(EventObserver $observer)
    {
        $faqParams = $this->request->getParam('groups');
        $faqUrlVal = $faqParams['seo']['fields'];
        if(key_exists('faq_url', $faqUrlVal)) {
            $urlKey = str_replace(' ', '-', $faqUrlVal['faq_url']['value']);
            $filterUrlKey = preg_replace('/[^A-Za-z0-9\-]/', '', $urlKey);
            $this->configWriter->save('faqtab/seo/faq_url', $filterUrlKey);
            $storeId = $this->storeManager->getStore()->getId();
            $faqUrl = $this->scopeConfig->getValue(
                'faqtab/seo/faq_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
            $urlRewriteModel = $this->urlRewriteFactory->create();
            $rewritecollection = $urlRewriteModel->getCollection()
                ->addFieldToFilter('request_path', self::REQUEST_PATH)
                ->addFieldToFilter('store_id', $storeId)
                ->getFirstItem();
            $urlRewriteModel->load($rewritecollection->getId());
            $urlRewriteModel->setStoreId($storeId);
            $urlRewriteModel->setTargetPath($faqUrl);
            $urlRewriteModel->setRequestPath(self::REQUEST_PATH);
            $urlRewriteModel->setredirectType(301);
            $urlRewriteModel->save();
        }
        return $this;
    }
}
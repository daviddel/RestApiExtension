<?php

namespace DavidDel\RestApi\RestApiExtension;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer as InitializerInterface;

class ContextInitializer implements InitializerInterface
{
    protected $baseUrl;

    /**
     * @param string $baseUrl
     */
    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    /**
     * @param Context $context
     *
     * @return bool
     */
    public function supports(Context $context)
    {
        return $context instanceof RestApiContext;
    }

    /**
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if (!$context instanceof RestApiContext) {
            return;
        }

        $context->setConfiguration($this->baseUrl);
    }
}

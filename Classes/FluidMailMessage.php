<?php
namespace Smichaelsen\FluidMailMessage;

use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;

/**
 * This class represents an email which can be rendered with fluid.
 * You can use all the possibilities of TYPO3's MailMessage but instead of
 * using ->setBody() and ->send() you rather use ->render($template) to
 * render and send a fluid template.
 * Before rendering you can ->setControllerContext() and ->assign() variables.
 */
class FluidMailMessage extends MailMessage
{

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext
     */
    protected $controllerContext;

    /**
     * @var array
     */
    protected $templateRootPaths;

    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     * @inject
     */
    protected $view;

    /**
     * @param ControllerContext $controllerContext
     */
    public function setControllerContext(ControllerContext $controllerContext)
    {
        $this->controllerContext = $controllerContext;
        $this->view->setControllerContext($controllerContext);
    }

    /**
     * @param array $templateRootPaths
     */
    public function setTemplateRootPaths(array $templateRootPaths)
    {
        $this->templateRootPaths = $templateRootPaths;
    }

    /**
     * Assigns a key value pair to the fluid template
     *
     * @param string $key
     * @param mixed $value
     */
    public function assign($key, $value)
    {
        $this->view->assign($key, $value);
    }

    /**
     * See ->setTemplate() for possibilities to express the template path
     *
     * @param string $template
     */
    public function render($template)
    {
        $this->setTemplate($template);
        $content = $this->view->render();
        $this->setBody($content);
        $this->send();
    }

    /**
     * You can either provide a absolute template path or a template path
     * beginning with 'EXT:'.
     *
     * If you have provided a ControllerContext (->setControllerContext()) you
     * can also just provide the file name when your template lies in
     * EXT:yourext/Resources/Private/Templates/Mail/
     *
     * If you have provided templateRootPaths (->setTemplateRootPaths)
     *
     * And finally you can also provide the full template source as a string.
     *
     * @param string $template
     */
    protected function setTemplate($template)
    {
        $possibleFullTemplatePaths = array(
            $template,
            GeneralUtility::getFileAbsFileName($template),
        );
        if (!empty($this->templateRootPaths)) {
            foreach ($this->templateRootPaths as $templateRootPath) {
                $path = rtrim($templateRootPath, '/') . '/Mail/' . $template;
                $possibleFullTemplatePaths[] = $path;
                $possibleFullTemplatePaths[] = GeneralUtility::getFileAbsFileName($path);
                $possibleFullTemplatePaths[] = $path . '.html';
                $possibleFullTemplatePaths[] = GeneralUtility::getFileAbsFileName($path) . '.html';
            }
        }
        if ($this->controllerContext instanceof ControllerContext) {
            $possibleFullTemplatePaths[] = ExtensionManagementUtility::extPath($this->controllerContext->getRequest()->getControllerExtensionKey()) . 'Resources/Private/Templates/Mail/' . $template . '.html';
            $possibleFullTemplatePaths[] = ExtensionManagementUtility::extPath($this->controllerContext->getRequest()->getControllerExtensionKey()) . 'Resources/Private/Templates/Mail/' . $template;
        }
        $templateIsFile = FALSE;
        foreach ($possibleFullTemplatePaths as $possibleFullTemplatePath) {
            if (file_exists($possibleFullTemplatePath)) {
                $this->view->setTemplatePathAndFilename($possibleFullTemplatePath);
                $templateIsFile = TRUE;
                break;
            }
        }
        if (!$templateIsFile) {
            $this->view->setTemplateSource($template);
        }
    }

}

<?php
/**
 * @copyright Copyright (c) 2018-2020 Basic App Dev Team
 * @link https://basic-app.com
 * @license MIT License
 */
namespace BasicApp\Asset;

use CodeIgniter\View\RendererInterface;

abstract class BaseAsset
{

    protected $_id;

    public $meta = [];

    public $views = [];

    public $css = [];

    public $js = [];

    public $beginBodyCss = [];

    public $beginBodyJs = [];

    public $beginBodyViews = [];

    public $endBodyCss = [];

    public $endBodyJs = [];

    public $endBodyViews = [];

    public $depends = [];

    public function __construct(string $id)
    {
        $this->_id = $id;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function renderView($view, array $params = []) : string
    {
        $customFile = 'vendor/' . str_replace('\\', '/', $view);

        if (service('locator')->locateFile($customFile, 'views'))
        {
            return view($customFile, $params, ['saveData' => false]);
        } 

        return view($view, $params, ['saveData' => false]);
    }

    public function renderMeta($name, $content = '', $attribute = 'name')
    {
        helper('meta');

        return meta_tag($name, $content, $attribute);
    }

    public function renderJs($src = '', $indexPage = false) : string
    {
        helper('html');

        return script_tag($src, $indexPage);
    }

    public function renderCss(
            $href = '', 
            string $rel = 'stylesheet', 
            string $type = 'text/css', 
            string $title = '', 
            string $media = '', 
            bool $indexPage = false, 
            string $hreflang = '') : string {

        helper('html');

        return link_tag($href, $rel, $type, $title, $media, $indexPage, $hreflang);
    }

    public function beforeRegister(RendererInterface $view)
    {
    }

    public function afterRegister(RendererInterface $view)
    {
    }

    public function getHead() : string
    {
        $return = '';

        foreach($this->css as $css)
        {
            $return .= $this->renderCss($css);
        }

        foreach($this->js as $js)
        {
            $return .= $this->renderJs($js);
        }

        foreach($this->meta as $key => $meta)
        {
            if (is_array($meta))
            {
                $return .= $this->renderMeta($meta);
            }
            else
            {
                $return .= $this->renderMeta($key, $meta);
            }
        }

        foreach($this->views as $key => $view)
        {
            if (is_array($view))
            {
                $return .= $this->renderView($key, $view);
            }
            else
            {
                $return .= $this->renderView($view);
            }
        }        

        return $return;
    }

    public function getBeginBody() : string
    {
        $return = '';

        foreach($this->beginBodyCss as $css)
        {
            $return .= $this->renderCss($css);
        }

        foreach($this->beginBodyJs as $js)
        {
            $return .= $this->renderJs($js);
        }

        foreach($this->beginBodyViews as $key => $view)
        {
            if (is_array($view))
            {
                $return .= $this->renderView($key, $view);
            }
            else
            {
                $return .= $this->renderView($view);
            }
        }        

        return $return;
    }

    public function getEndBody() : string
    {
        $return = '';

        foreach($this->endBodyCss as $css)
        {
            $return .= $this->renderCss($css);
        }

        foreach($this->endBodyJs as $js)
        {
            $return .= $this->renderJs($js);
        }

        foreach($this->endBodyViews as $key => $view)
        {
            if (is_array($view))
            {
                $return .= $this->renderView($key, $view);
            }
            else
            {
                $return .= $this->renderView($view);
            }
        }

        return $return;
    }

    public function isRegistred(RendererInterface $view) : bool
    {
        $assetId = $this->getId();

        $data = $view->getData();
        
        if (array_key_exists('__assets', $data) && (array_search($assetId, $data['__assets']) !== false))
        {
            return true;
        }

        return false;
    }

    public function setRegistred(RendererInterface $view)
    {
        $assetId = $this->getId();

        $data = $view->getData();

        if (array_key_exists('__assets', $data) && (array_search($assetId, $data['__assets']) !== false))
        {
            return false;
        }

        $data['__assets'][] = $assetId;

        $view->setData($data);

        return true;
    }

    public function register(RendererInterface $view, array $params = [], $reset = false)
    {
        if (!$this->setRegistred($view))
        {
            return;
        }
 
        $this->beforeRegister($view);

        foreach($this->depends as $key => $value)
        {
            if (is_array($value))
            {
                service($key)->register($view, $value);
            }
            else
            {
                service($value)->register($view);
            }
        }

        $this->registerSection($view, 'head', $this->getHead());

        $this->registerSection($view, 'beginBody', $this->getBeginBody());

        $this->registerSection($view, 'endBody', $this->getEndBody());

        $this->afterRegister($view);
    }

    public function registerSection(RendererInterface $view, $section, $content)
    {
        $view->section($section);

        echo $content;

        $view->endSection();
    }

}
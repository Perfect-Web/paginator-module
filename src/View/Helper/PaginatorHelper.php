<?php
/**
 * @copyright Copyright (c) 2014 Nico Vogelaar (http://nicovogelaar.nl)
 * @license   http://www.opensource.org/licenses/mit-license.php  MIT License
 * @link      http://nicovogelaar.nl
 */
namespace Nicovogelaar\Paginator\View\Helper;

use Zend\Form\Element;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Nicovogelaar\Paginator\Paginator;
use Nicovogelaar\Paginator\Form\FilterForm;

/**
 * PaginatorHelper
 *
 * @author Nico Vogelaar <nico@nicovogelaar.nl>
 */
class PaginatorHelper extends AbstractHelper implements ServiceLocatorAwareInterface
{

    use ServiceLocatorAwareTrait;

    /**
     * Paginator
     *
     * @var Paginator
     */
    protected $paginator;

    /**
     * Url
     *
     * @var string
     */
    protected $url;

    /**
     * Service locator
     *
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator;

    /**
     * Invokable
     *
     * @param Paginator $paginator Paginator
     *
     * @return Paginator
     */
    public function __invoke(Paginator $paginator = null)
    {
        if (null !== $paginator) {
            $this->setPaginator($paginator);
        }

        if (null === $this->url) {
            $this->url = $this->view->url(null, array(), array(), true);
        }

        return $this;
    }

    /**
     * Sets url
     *
     * @param string $url Url
     *
     * @return Paginator
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Generate sorting url
     *
     * @param string $key Key
     *
     * @return string
     */
    public function sortingUrl($key)
    {
        $parameters = $this->paginator->getParameters();

        $sortFieldName = $parameters->getName('sort_field');
        $sortDirectionName = $parameters->getName('sort_direction');

        if (null === $parameters->getSortDirection()) {
            $sortDirection = 'desc';
        } elseif ('asc' === $this->sortDirection($key)) {
            $sortDirection = 'desc';
        } else {
            $sortDirection = 'asc';
        }

        $params = $this->paginator->getData();
        $params[$sortFieldName] = $key;
        $params[$sortDirectionName] = $sortDirection;

        return $this->url . '?' . http_build_query($params);
    }

    /**
     * Gets the current sort direction for the specified key.
     *
     * @param string $key Key
     *
     * @return string|boolean
     */
    public function sortDirection($key)
    {
        $parameters = $this->paginator->getParameters();

        $sortField = $parameters->getSortField();
        $sortDirection = $parameters->getSortDirection();

        if ($key !== $sortField) {
            return false;
        }

        return 'desc' === $sortDirection ? 'desc' : 'asc';
    }

    /**
     * Generate page url
     *
     * @param integer $page Page number
     *
     * @return string
     */
    public function pageUrl($page)
    {
        $parameters = $this->paginator->getParameters();

        $params = $this->paginator->getData();
        $params[$parameters->getName('page')] = $parameters->getPage();

        if ($params[$parameters->getName('route')]) {
            $params[$parameters->getName('page')] = $page;
            $url = $this->view->url(null, $params, array(), true);
        }
        else {
            $query = http_build_query($params);
            $url = $this->url . ('' != $query ? '?' . $query : '');
        }

        return $url;
    }

    /**
     * Gets the filter form
     *
     * @return FilterForm
     */
    public function filterForm()
    {
        $filters = $this->paginator->getFilters();
        $parameters = $this->paginator->getParameters();

        $fm = $this->serviceLocator
            ->getServiceLocator()
            ->get('FormElementManager');

        $form = $fm->get(
            'Nicovogelaar\Paginator\Form\FilterForm',
            array('filters' => $filters)
        );

        $form->setData(
            array(
                'query' => $parameters->getQuery(),
                'filter' => $parameters->getFilters()
            )
        );

        return $form;
    }

    /**
     * Get the paginator
     *
     * @return Paginator
     */
    public function getPaginator()
    {
        return $this->paginator;
    }

    /**
     * Set the paginator
     *
     * @param Paginator $paginator Paginator
     *
     * @return PaginatorHelper
     */
    public function setPaginator(Paginator $paginator)
    {

        $this->paginator = $paginator;

        $params = $this->paginator->getData();
        $parameters = $paginator->getParameters();
        $event = $this->getServiceLocator()->getServiceLocator()->get('Application')->getMvcEvent();

        if ($params[$parameters->getName('route')]) {
            $this->paginator->getPaginator()->setCurrentPageNumber(
                $event->getRouteMatch()->getParam($parameters->getName('page'), 1)
            );
        }

        return $this;
    }

}
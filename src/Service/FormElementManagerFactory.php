<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\Form\Form;
use Laminas\Form\FormElementManager;
use Laminas\ServiceManager\ServiceLocatorInterface;

class FormElementManagerFactory extends AbstractPluginManagerFactory
{
    const PLUGIN_MANAGER_CLASS = 'Laminas\Form\FormElementManager';

    /**
     * Create and return the MVC controller plugin manager
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return FormElementManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $plugins = parent::createService($serviceLocator);
        if ($serviceLocator->has('Di')) {
            $di = $serviceLocator->get('Di');
            $im = $di->instanceManager();
            if (!$im->getTypePreferences('Laminas\Form\FormInterface')) {
                $form = new Form;
                $im->setTypePreference('Laminas\Form\FormInterface', array($form));
            }
            $plugins->addPeeringServiceManager($serviceLocator);
            $plugins->setRetrieveFromPeeringManagerFirst(true);
        }
        return $plugins;
    }
}

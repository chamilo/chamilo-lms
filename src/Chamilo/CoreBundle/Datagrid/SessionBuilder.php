<?php

namespace Chamilo\CoreBundle\DataGrid;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Thrace\DataGridBundle\DataGrid\DataGridFactoryInterface;

class SessionBuilder
{

    const IDENTIFIER = 'user_management';
    protected $factory;
    protected $translator;
    protected $router;
    protected $em;


    public function __construct (DataGridFactoryInterface $factory, TranslatorInterface $translator, RouterInterface $router,
        EntityManager $em)
    {
        $this->factory = $factory;
        $this->translator = $translator;
        $this->router = $router;
        $this->em = $em;
    }

    public function build ()
    {

        $dataGrid = $this->factory->createDataGrid(self::IDENTIFIER);
        $dataGrid
            ->setCaption($this->translator->trans('user_management_datagrid.caption'))
            ->setColNames(array(
                    $this->translator->trans('column.firstName'),
                    $this->translator->trans('column.lastName'),
                    $this->translator->trans('column.grandTotal'),
                    $this->translator->trans('column.enabled'),
                ))
            ->setColModel(array(
                    array(
                        'name' => 'firstName', 'index' => 'u.firstName', 'width' => 200,
                        'align' => 'left', 'sortable' => true, 'search' => true,
                    ),
                    array(
                        'name' => 'lastName', 'index' => 'u.lastName', 'width' => 200,
                        'align' => 'left', 'sortable' => true, 'search' => true,
                    ),
                    array(
                        'name' => 'total', 'index' => 'total', 'width' => 200, 'aggregated' => true,
                        'align' => 'left', 'sortable' => true, 'search' => true,
                        'formatter' => 'currency',
                    ),
                    array(
                        'name' => 'enabled', 'index' => 'u.enabled', 'width' => 30,
                        'align' => 'left', 'sortable' => true, 'search' => true,
                        'formatter' => 'checkbox',  'search' => true, 'stype' => 'select',
                        'searchoptions' => array(
                            'value' => array(
                                1 => 'enable',
                                0 => 'disabled',
                            )
                        )
                    ),
                ))
            ->setQueryBuilder($this->getQueryBuilder())
            ->enableSearchButton(true)
        ;

        return $dataGrid;
    }


    protected function getQueryBuilder()
    {
        $qb = $this->em->getRepository('ChamiloUserBundle:User')->createQueryBuilder
        ('u');
        $qb
            ->select('u.id, u.firstName, u.lastName, SUM(o.total) as total, u.enabled, u')
            ->leftJoin('u.orders', 'o')
            ->groupBy('u.id')
        ;

        return $qb;
    }
}

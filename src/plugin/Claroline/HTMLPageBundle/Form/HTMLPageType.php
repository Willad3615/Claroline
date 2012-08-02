<?php

namespace Claroline\HTMLPageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class HTMLPageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('archive', 'file');
        $builder->add('index_page', 'text');
    }

    public function getName()
    {
        return 'html_page_form';
    }
}
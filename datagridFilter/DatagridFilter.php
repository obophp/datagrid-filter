<?php

namespace obo\Utils;

abstract class DatagridFilter extends \Nette\Application\UI\Control implements \obo\Interfaces\IFilter {

    /**
     * @var \Nette\Application\UI\Form
     */
    protected $form = null;
    protected $defaultValues = array();
    protected $datagridSnippetName = null;
    protected $templateFile = null;

    public function __construct($parent = NULL, $name = NULL) {
        parent::__construct($parent, $name);
        if (\is_null($this->templateFile)) $this->templateFile = dirname(__FILE__) . "/DatagridFilter.latte";
    }

    abstract public function getSpecification();

    public function render() {
        $this->template->setFile($this->templateFile);
        $this->template->render();
    }

    protected function filterCriteria(){
        return (isset($this->getSessionStorage()->data) AND $data = $this->getSessionStorage()->data AND (is_array($data) OR \is_object($data))) ? $this->getSessionStorage()->data :  $this->setFilterCriteria($this->defaultValues);
    }

    protected function setFilterCriteria($criteria){
        return $this->getSessionStorage()->data = $criteria;
    }

    public function isActive(){
        return $this->filterCriteria() == $this->defaultValues;
    }

    protected function fillFilterForm($forced = false){

        if ($forced) {
            $this->form->setValues($this->filterCriteria(), true);
        } else {
            $this->form->setDefaults($this->filterCriteria());
        }

        $this->afterChangeFilter();
    }

    public function changeFilter(\Nette\Application\UI\Form $form){
        $this->setFilterCriteria($form->values);
        $this->fillFilterForm();
    }

    public function resetFilter(){
        $this->setFilterCriteria($this->defaultValues);
        $this->fillFilterForm(true);
    }

    public function notifyChangeFilter(){
        $this->afterChangeFilter();
    }

    protected function afterChangeFilter(){
        if (\is_null($this->datagridSnippetName)) return;
        $this->parent->invalidateControl($this->datagridSnippetName);
    }

    public function changeFilterCriteria(array $criteria, $onlyOverwrite = true) {

        if ($onlyOverwrite) {
            $originalCriteria = $this->filterCriteria();
            $criteria = \array_merge($originalCriteria, $criteria);
        }

        $this->setFilterCriteria($criteria);
        $this->fillFilterForm(true);
    }

    protected function formFactory($name) {
        return new \Nette\Application\UI\Form($this, $name);
    }

    final protected function createComponentForm($name) {
        $this->form = $form = $this->formFactory($name);
        $this->constructFilterForm($form);
        if ($form["reset"]->isSubmittedBy()) $this->resetFilter();
        $form->onSuccess[] = callback($this, 'changeFilter');
        $this->fillFilterForm();

        return $form;
    }

    protected function getSessionStorage() {
        return $this->presenter->context->session->getSection(strtoupper($this->name));
    }
}
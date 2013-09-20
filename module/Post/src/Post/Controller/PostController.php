<?php

namespace Post\Controller;

use Zend\Db\Sql\Ddl\Column\Text;
use Zend\Filter\Int;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Stdlib\Request;
use Zend\View\Model\ViewModel;
use Post\Entity\Post;
use Post\Form\PostForm;
use Doctrine\ORM\EntityManager;
use Zend\Filter\FilterChain;

class PostController extends AbstractActionController
{

    /**
     * @var Doctrine\ORM\EntityManager
     */
    protected $em;

    public function setEntityManager(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getEntityManager()
    {
        if (null === $this->em) {
            $this->em = $this->getServiceLocator()->get('doctrine.entitymanager.orm_default');
        }

        return $this->em;
    }


    // indexAction - List all the posts
    public function indexAction()
    {
        return new ViewModel(array(
            'posts' => $this->getEntityManager()->getRepository('Post\Entity\Post')->findall()
        ));

    }


    // addPostAction - add a post, back to index if success
    public function addPostAction()
    {
        // create form instance, then set the value of the submit button to Add
        $form = new PostForm();
        $form->get('send')->setValue('Add');

        // if $request isPost(), create Post instance, set the InputFilter,
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = new Post();
            $form->setInputFilter($post->getInputFilter()); // ??

//            echo '<pre />';
//            var_dump($request->getPost());

            $form->setData($request->getPost());

            // if $form isValid() true, post instance would grab data from the form, put into database
            if ($form->isValid()) {
                $post->populate($form->getData());
                $post->setDate(date_create()); //??? timezone??

                $this->getEntityManager()->persist($post);
                $this->getEntityManager()->flush();

                // back to index
                return $this->redirect()->toRoute('post');
            }
        }

        return array('form' => $form);
    }

    public function editPostAction()
    {
        // get post by id
        $id = (int)$this->params()->fromRoute('id', 0); //
        if (!$id) {
            return $this->redirect()->toRoute('post', array(
                'action' => 'addpost'
            ));
        }

        try {
            $post = $this->getEntityManager()->find('Post\Entity\Post', $id);
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('post');
        }

        $createDate = $post->getDate();

        // bind to form
        $form = new PostForm();
        $form->bind($post);
        $form->get('send')->setAttribute('value', 'edit');

        // if isPost(), go get posted values from entity (and inputfilter to validate)
        $request = $this->getRequest();
        if ($request->isPost()) {

            $form->setInputFilter($post->getInputFilter());
            $form->setData($request->getPost());

            //if valid, go database
            if ($form->isValid()) {
                /**注：isValid()函数中，最后会有bindValues()的动作，注意数据库中如果有需要保持不变的字段，
                 * 要在isValid()之前提前取出（可保存于变量中），因为bindValues()之后会清空bound的object？（可能？）
                 *
                 */
//                $form->bindValues();  // isValid中有bindValues()动作，所以这里可以去掉
                $post->setDate($createDate);
                $this->getEntityManager()->flush();

                // after save the edit, redirect to module index
                return $this->redirect()->toRoute('post', array(
                    'action' => 'index',
                ));

            }
        }

        // return form and id
        return array(
            'form' => $form,
            'id'   => $id,
        );
    }

    public function deletePostAction()
    {

        // get post by id
        $id = (int)$this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toRoute('post', array('action' => 'index'));
        }

        try {
            $post = $this->getEntityManager()->find('Post\Entity\Post', $id);
        } catch (\Exception $ex) {
            return $ex;
        }

        // isPost? go delete if is Post
        $request = $this->getRequest();
        if ($request->isPost()) {
            $del = $request->getPost('del', 'No'); // 获得form post过来的del的值，default值为No（如果未获取到值，则为No？）
            // yes or no? go delete if yes
            if ($del == 'Yes') {
                $this->getEntityManager()->Remove($post);
                $this->getEntityManager()->flush();
            }

            // if No, back to index
            return $this->redirect()->toRoute('post');
        }

        return array(
            'id'   => $id,
            'post' => $this->getEntityManager()->find('Post\Entity\Post', $id)
        );

        // if not isPost, return the post, along with the id

    }


}
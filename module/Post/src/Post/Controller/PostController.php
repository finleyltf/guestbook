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

use Zend\Validator\File\Size;
use Zend\File\Transfer\Adapter\Http as FileTransferAdapter;

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

            $data = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );

            $form->setData($data);

            // if $form isValid() true, post instance would grab data from the form, put into database
            if ($form->isValid()) {

                // form is valid, upload image file to directory desired by \Zend\File\Transfer\Adapter\Http component,
                // and add validation like file size to validate maximal file size
                $file = $this->params()->fromFiles('image');
                $this->validatedUpload($file, $form);


                $post->populate($form->getData());
                $post->setDate(date_create()); // timezone : ok


                // set the name of the uploaded file to the image
                $post->setImage($post->image['name']);


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
        $imageFileName = $post->getImage();

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
                $post->setImage($imageFileName);

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


    public function validatedUpload($file, $form)
    {

        $size = new Size(array('max' => 10000000)); // maximum bytes filesize

        $adapter = new FileTransferAdapter();

        // validation can be more than one ...
        $adapter->setValidators(array($size), $file['name']);

        if (!$adapter->isValid()) {
            $dataError = $adapter->getMessages();
            $error     = array();
            foreach ($dataError as $key => $row) {
                $error[] = $row;
            } //set formElementErrors
            $form->setMessages(array('image' => $error));
        } else {
            // valid, lets do upload here
            $adapter->setDestination(getcwd() . '/public/img/uploads'); // getcwd() get the current working directory
//            $adapter->setDestination(dirname(__DIR__) . '/assets');

//            echo '<pre>';
//            var_dump(move_uploaded_file($file['tmp_name'], $adapter->getDestination()));
//            var_dump($adapter->getFileInfo($file['name']));
//            echo '</pre>';


            if ($adapter->receive($file['name'])) {
                chmod($adapter->getDestination() . '/' .$file['name'], 0755);
            } else {
                throw new \Exception ('upload failed!!!');
            }

        }

    }

}
<?php
namespace Post\Form;

use Zend\Form\Form;

class PostForm extends Form
{

    public function __construct($name = null)
    {
        parent::__construct('post');
        $this->setAttribute('method', 'post');
        $this->setAttribute('enctype','multipart/form-data'); // add this line if upload is needed!!!
        /**
         * Post form
         *
         * id
         * guest_name
         * date
         * review
         * subject
         * rating
         * image
         *
         */

        $this->add(array(
            'name'       => 'id',
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name'       => 'guest_name',
            'attributes' => array(
                'type'        => 'text',
                'placeholder' => 'Guest Name',
            ),
            'options'    => array(
                'label' => 'Guest Name',
            ),
        ));

        /**
         * 要插入post当时的时间，这里如何操作date？
         */
        /*
        $this->add(array(
            'name' => 'date',
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));
        */

        $this->add(array(
            'name'       => 'subject',
            'attributes' => array(
                'type'        => 'textarea',
                'placeholder' => 'Subject',
            ),

        ));

        $this->add(array(
            'name'       => 'rating',
            'type'       => 'Select',
            'attributes' => array(
                // 'type' => 'select',  // ???这里为什么不行？
            ),
            'options' => array(
//                'label'         => 'How do you rate our site?', // 这个label不会显示，作何用？
                'empty_option'  => 'Please rate our site',
                'value_options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                ),
            )

        ));

        /**
         * textarea ??
         */
        $this->add(array(
            'name'       => 'review',
            'attributes' => array(
                'type'        => 'textarea',
                'rows'        => '8',
//                'cols'        => '30',
                'placeholder' => 'Review',
//                'class' => 'input-large',
            ),

        ));

        $this->add(array(
            'name'       => 'send',
//            'type' => 'Submit', // 注意大写Submit，和上面的add方法区别。不同？
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Go',
                ''
            ),
        ));

        $this->add(array(
           'name' => 'image',
            'type' => 'File',

        ));

    }
}
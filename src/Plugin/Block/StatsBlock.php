<?php
/**
 * @file
 * Contains \Drupal\stats_box\Plugin\Block\StatsBlock.
 */
namespace Drupal\stats_box\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a 'StatsBox' block.
 *
 * @Block(
 *  id = "stats_box",
 *  admin_label = @Translation("Stats Box"),
 * )
 */
class StatsBlock extends BlockBase {
  /**
   * {@inheritdoc}
   */

  public function blockForm($form, FormStateInterface $form_state) {
  
  // Content Type
  $contentTypes = \Drupal::service('entity.manager')->getStorage('node_type')->loadMultiple();

$contentTypesList = [];
foreach ($contentTypes as $contentType) {
    $contentTypesList[$contentType->id()] = $this->t($contentType->label());
}
  $form['type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Content Type'),
      '#description' => $this->t('Select the Content Type that you need'),
      '#options' => $contentTypesList ,
      '#default_value' => isset($this->configuration['type']) ? $this->configuration['type'] : '',
      '#required' => TRUE,
	  // '#ajax' => array(
			// 'callback' => [$this,'selectedElement'],
			'wrapper' => 'field',
		   // ),
    );
    
  // Option published
  $form['published'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Published'),
      '#description' => $this->t('Filter by published node'),
      '#default_value' => isset($this->configuration['published']) ? $this->configuration['published'] : ''
    );   
	$form['prefix'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#description' => $this->t('Prefix €, $ ...'),
      '#default_value' => isset($this->configuration['prefix']) ? $this->configuration['prefix'] : ''
    );  
  //Field name
  foreach ($contentTypes as $contentType) {
	  $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $contentType->id());
	  foreach ($fields as $field_name => $field_definition) {
		  if (!empty($field_definition->getTargetBundle())) {     
			$listFields[$field_name] = $field_definition->getLabel()."(".$contentType->label().")";                  
		  }
		}
}
  
		
  $form['field'] = array(
      '#type' => 'select',
      '#title' => $this->t('Field name'),
      '#description' => $this->t('Field name to calc'),
	  '#options' => $listFields ,
      '#default_value' => isset($this->configuration['field']) ? $this->configuration['field'] : '',
      '#required' => TRUE,
	  '#prefix' => '<div id="what">',
      '#suffix' => '</div>',
    );    
  // Calc Option
    $form['calc'] = array(
      '#type' => 'select',
      '#title' => $this->t('Calc Option'),
      '#description' => $this->t('Calc Option'),
      '#options' => array(
        'count' => $this->t('COUNT'), 
        'sum' => $this->t('SUM'), 
        'max' => $this->t('MAX'), 
        'min' => $this->t('MIN'), 
      ),
      '#default_value' => isset($this->configuration['calc']) ? $this->configuration['calc'] : 'SUM',
      '#required' => TRUE,
    );    
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['calc'] = $form_state->getValue('calc');
    $this->configuration['type'] = $form_state->getValue('type');
    $this->configuration['field'] = $form_state->getValue('field');
    $this->configuration['published'] = $form_state->getValue('published');
    $this->configuration['prefix'] = $form_state->getValue('prefix');
  }
  
    public function build() {
		$val = 0;
		$connection = \Drupal::database();
		
		$opt=$this->configuration['calc'];
		$type=$this->configuration['type'];
		$pub=$this->configuration['published'];
		$field=$this->configuration['field'];
		$prefix=$this->configuration['prefix'];
		
		$result = \Drupal::entityQuery('node')
			->condition('type', $type);
		if($pub)
			$result->condition('status', 1);
		
		if ($opt=='count'){			 
			 $val = $result->count()->execute();
		}else{
			$nids=$result->execute();
			$nodes=  \Drupal\node\Entity\Node::loadMultiple($nids);;
			$values=array();
			foreach ($nodes as $node){
			  $values[] = $node->get($field)->value;
			}
			
			if ($opt=='sum'){
				$val = array_sum($values);
			}
			if ($opt=='max'){
				$val = max($values);
			}
			if ($opt=='min'){
				$val = min($values);
			}
			if ($opt=='average'){
				$val = array_sum($values)/count($values);
			}
		}
		
				 
		   
		  
		return array(
		  '#type' => 'markup',
		  '#markup' =>  '<span class="value number h1">'.$val .'</span><span class="prefix h3">'.$prefix .'</span>',
		);
	  }
	 
  
  
  /*
	public function ajax_box_callback(array &$form, FormStateInterface $form_state)    {
		  // $elem = [
		  // '#type' => 'textfield',
			// '#size' => '60',
			// '#disabled' => TRUE,
			// '#value' => 'Hello, ' . $form_state->getValue('type') . '!',
			// '#attributes' => [
			  // 'id' => ['edit-output'],
			// ],
		  // ];

		  return $form['field'];
		}
	
	{
		$listFields= array();
		$type = $form_state->getValue('type');
		$fields = $this->entityFieldManager->getFieldDefinitions('node', 'article');
		foreach ($fields as $field_name => $field_definition) {
		  if (!empty($field_definition->getTargetBundle())) {     
			$listFields[$field_name] = $field_definition->getLabel();                  
		  }
		}
		 $form['field'] = array(
		  '#type' => 'select',
		  '#title' => $this->t('Field name'),
		  '#description' => $this->t('Field name to calc'),
		  '#options' => $listFields,
		  '#default_value' => isset($this->configuration['field']) ? $this->configuration['field'] : '',
		  '#required' => TRUE,
		);    
		 $form_state->setRebuild();
    return $form['field'];
	}*/
}
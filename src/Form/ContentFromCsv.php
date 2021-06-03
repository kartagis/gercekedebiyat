<?php

namespace Drupal\gercekedebiyat\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

class ContentFromCsv extends FormBase {

  /**
   * @return string|void
   */
  public function getFormId() {
    return 'gercekedebiyat.upload';
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['csv'] = [
      '#type' => 'managed_file',
      '#title' => 'İçerikleri barındıran CSV dosyasını yükleyin.',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#upload_location' => 'public://',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Yükle',
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fid = $form_state->getValue('csv');
    $input = File::load(reset($fid));
    $input->setPermanent();
    $uri = \Drupal::service('file_system')->realpath($input->getFileUri());
    $csv = fopen($uri, "r");
    $batch = [
      'title' => 'Creating nodes',
      'operations' => [],
      'init_message' => 'Initialising',
      'progress_message' => 'Processed @current out of @total',
      'error_message' => 'An error occurred during processing',
      'finished' => 'Drupal\gercekedebiyat\Form\ContentFromCsv::batchFinished',
    ];
    while (!feof($csv)) {
      $content = fgetcsv($csv);
      //$date = \DateTime::createFromFormat('Y-m-d H:i:s', $content[8]);
      //$ts = $date->getTimestamp();
      $file = File::create([
        'uid' => 1,
        'uri' => $content[5],
      ]);
      $file->save();
      $node = Node::create([
        'type' => 'yazi',
        'uid' => 1,
        'field_eski_id' => $content[0],
        'field_yazi_kategorisi' => [20],
        'title' => $content[4] ? strip_tags($content[4]) : 'Title missing',
        'field_spot' => $content[6] ?? 'Spot missing',
        'body' => [
          'summary' => '',
          'value' => $content[7] ?? 'Body missing',
          'format' => 'full_html'
        ],
        'field_one_cikan_gorsel' => [
          [
            "target_id" => $file->id(),
            "alt" => $content[4],
            "title" => $content[4],
          ],
        ],
        'status' => 1,
        'created' => [strtotime($content[8])],
      ]);
      //$node->setCreatedTime(strtotime($content[6]));
      ;
      $node->save();
      batch_set($batch);
    }
  }

  public static function batchFinished($success, $results, $operations) {
    if ($success) {
      \Drupal::messenger()->addMessage(t('Generated nodes'));
    }
  }

}
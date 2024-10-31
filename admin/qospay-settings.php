<?php
/**
 * Settings for Qospay Plugin.
 *
 */
defined('ABSPATH') || exit;
return array(

    'enabled' => array(
        'title' => 'Enable/Disable',
        'label' => 'Enable QoS Payment',
        'type' => 'checkbox',
        'description' => '',
        'default' => 'yes'
    ),
    'testmode' => array(
        'title' => 'Enable Production',
        'label' => 'Enable Production',
        'type' => 'checkbox',
        'description' => 'Unchecked means mode is Test',
        'default' => 'no',
        'desc_tip' => true,
    ),
    'production_qos_key' => array(
        'title' => 'Production Qos Key for checkout ',
        'type' => 'text',
        'description' => 'Production Qos Key for checkout.',
        'desc_tip' => true,
    )
    // 'production_clientid_mtn' => array(
    //     'title' => 'Production ClientID for MTN ',
    //     'type' => 'text',
    //     'description' => 'Production CLientID for MTN.',
    //     'desc_tip' => true,
    // ),
    // 'production_username_mtn' => array(
    //     'title' => 'Production Username for MTN',
    //     'type' => 'text',
    //     'description' => 'Production Username for MTN.',
    //     'desc_tip' => true,
    // ),
    // 'production_password_mtn' => array(
    //     'title' => 'Production Password for MTN',
    //     'type' => 'password',
    //     'description' => 'Production Password for MTN.',
    //     'desc_tip' => true
    // ),
    // 'production_clientid_moov' => array(
    //     'title' => 'Production ClientID for MOOV',
    //     'type' => 'text',
    //     'description' => 'Production CLientID for MOOV.',
    //     'desc_tip' => true,
    // ),
    // 'production_username_moov' => array(
    //     'title' => 'Production Username for MOOV',
    //     'type' => 'text',
    //     'description' => 'Production Username for MOOV.',
    //     'desc_tip' => true,
    // ),
    // 'production_password_moov' => array(
    //     'title' => 'Production Password for MOOV',
    //     'type' => 'password',
    //     'description' => 'Production Password for MOOV.',
    //     'desc_tip' => true
    // )
);

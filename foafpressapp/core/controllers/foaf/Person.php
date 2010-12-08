<?php

class Foaf_Person_Controller extends Foafpress_Controller
{
    public function get_request()
    {
        // -- Layout class -----------------------------------------------------
        
        $this->content->body_css_class = $this->RESOURCE->cssGetType();

        // -- Document Meta Data (for HTML Head) -------------------------------

        $resource_uri = $this->RESOURCE->uri; // save uri of shown resource
        $this->RESOURCE->uri = $this->pm->load('Foafpress')->URI_Document; // use uri of resource container
        $this->content->META_TITLE = $this->RESOURCE->getLiteral(array('rdfs_label', 'dc_title'));
        $this->content->META_DESCRIPTION = $this->RESOURCE->getLiteral(array('rdfs_comment', 'dc_description'));
        $this->RESOURCE->uri = $resource_uri; // restore uri of resource
        
        // -- Basics - Name, Info, Depiction -----------------------------------
        
        $this->content->name_or_nickname = $this->RESOURCE->getLiteral(array('name', 'nick'));
        $this->content->depiction = $this->RESOURCE->getImage();
        $this->content->short_description = $this->RESOURCE->getLiteral(array('bio_olb'));
        
        // -- Online Accounts --------------------------------------------------
        
        $list_of_account_objects = $this->RESOURCE->holdsAccount;
        $list_of_accounts = array();
        
        foreach ($list_of_account_objects as $account_object)
        {
            if (is_object($account_object) && $account_object->homepage)
            {
                $list_of_accounts[] = array(
                    'source-icon-class' => $account_object->getIconLayout($account_object->homepage[0]->uri),
                    'homepage-url' => $account_object->homepage[0]->uri,
                    'homepage-label' => $account_object->homepage[0]->getLiteral(array('rdfs_label', 'dc_titel'))
                );
            }
            unset($account_object);
        }
        
        $this->content->list_of_accounts = $list_of_accounts;
        unset($list_of_accounts);
        unset($list_of_account_objects);
        
        // -- Activity / Feeds -------------------------------------------------
        
        $activity = $this->RESOURCE->listActivity();
        if (isset($activity['stream'])) $this->content->activity = $activity;
        unset($activity);
        
        // -- Interests --------------------------------------------------------
        
        $list_of_interest_objects = $this->RESOURCE->interest;
        $list_of_interests = array();
        
        foreach ($list_of_interest_objects as $interest_object)
        {
            if (is_object($interest_object) && $interest_label = $interest_object->getLiteral(array('dc_title', 'rdfs_label')))
            {
                $interest_details = array();
                
                $interest_details['label'] = $interest_label;
                
                if ($description = $interest_object->getLiteral(array('dc_description', 'rdfs_comment')))
                {
                    $interest_details['description'] = $description;
                }
                unset($description);
                
                if ($homepage = array_merge($interest_object->foaf_homepage, $interest_object->foaf_page, $interest_object->foaf_primaryTopic) && isset($homepage[0]) && is_object($homepage[0]))
                {
                    $interest_details['link'] = $homepage[0]->uri;
                }
                elseif (substr($interest_object->uri, 0, 1) != '_')
                {
                    $interest_details['link'] = $interest_object->uri;
                }
                unset($homepage);
                
                $list_of_interests[] = $interest_details;
                
                unset($interest_details);
            }
            elseif (!is_object($interest_object))
            {
                $list_of_interests[] = array(
                    'label' => $interest_object
                );
            }
            unset($interest_object);
        }
        
        $this->content->list_of_interests = $list_of_interests;
        unset($list_of_interests);

        // -- Projects ---------------------------------------------------------
        
        $list_of_project_objects = array_unique(array_merge($this->RESOURCE->currentProject, $this->RESOURCE->pastProject)/*, SORT_REGULAR*/);;
        $list_of_projects = array();
        
        foreach ($list_of_project_objects as $project_object)
        {
            if (is_object($project_object) && $project_label = $project_object->getLiteral(array('doap_name', 'dc_title', 'rdfs_label', 'foaf_name')))
            {
                $project_details = array();
                
                $project_details['label'] = $project_label;
                
                if ($description = $project_object->getLiteral(array('doap_description', 'dc_description', 'rdfs_comment')))
                {
                    $project_details['description'] = $description;
                }
                unset($description);
                
                if ($homepage = array_merge($project_object->doap_homepage, $project_object->foaf_homepage/* ERROR wegen '-' TODO, $project_object->rdfohloh_ohloh-page*/) && isset($homepage[0]) && is_object($homepage[0]))
                {
                    $project_details['link'] = $homepage[0]->uri;
                }
                elseif (substr($project_object->uri, 0, 1) != '_')
                {
                    $project_details['link'] = $project_object->uri;
                }
                unset($homepage);
                
                $list_of_projects[] = $project_details;
                
                unset($project_details);
            }
            elseif (!is_object($project_object))
            {
                $list_of_projects[] = array(
                    'label' => $project_object
                );
            }
            unset($project_object);
        }
        
        $this->content->list_of_projects = $list_of_projects;
        unset($list_of_projects);

        // -- Skills -----------------------------------------------------------
        
        $list_of_resume_objects = $this->RESOURCE->rdfs_seeAlso('cv:CV');
        $list_of_skill_objects = array();
        
        if (is_array($list_of_resume_objects))
        {
            foreach ($list_of_resume_objects as $resume_object)
            {
                $list_of_skill_objects = array_unique(array_merge($list_of_skill_objects, $resume_object->cv_hasSkill));
            }
        }
        
        $list_of_skills = array();
        
        foreach ($list_of_skill_objects as $skill_object)
        {
            if (is_object($skill_object) && $skill_label = $skill_object->getLiteral(array('cv_skillName')))
            {
                $skill_level = $skill_object->cv_skillLevel;
                $list_of_skills[] = array(
                    'label' => $skill_label,
                    'level' => $skill_level?$skill_level[0]:false
                );
                unset($skill_level);
                unset($skill_label);
            }

            unset($skill_object);
        }

        $this->content->list_of_skills = $list_of_skills;
        unset($list_of_skill_objects);
        unset($list_of_skills);

        // -- VCards -----------------------------------------------------------
        
        $list_of_VCard_objects = array_unique(array_merge($this->RESOURCE->ov_businessCard, $this->RESOURCE->foaf_businessCard));
        
        $list_of_contact_objects = array(
            'Work' => array(
                'adr' => array(), 'tel' => array(), 'fax' => array(), 'email' => array()
            ),
            'Home' => array(
                'adr' => array(), 'tel' => array(), 'fax' => array(), 'email' => array()
            )
        );
        
        $list_of_contacts = array(
            'Work' => array(
                'Address' => array(), 'Phone' => array(), 'Fax' => array(), 'Email' => array()
            ),
            'Home' => array(
                'Address' => array(), 'Phone' => array(), 'Fax' => array(), 'Email' => array()
            )
        );

        foreach ($list_of_VCard_objects as $VCard_object)
        {
            $list_of_contact_objects['Work']['adr'] = array_unique(array_merge(
                                                        $list_of_contact_objects['Work']['adr'],
                                                        $VCard_object->vcard_adr('vcard:Work')));
            $list_of_contact_objects['Work']['tel'] = array_unique(array_merge(
                                                        $list_of_contact_objects['Work']['tel'],
                                                        $VCard_object->vcard_tel('vcard:Work', '-vcard:Fax')));
            $list_of_contact_objects['Work']['fax'] = array_unique(array_merge(
                                                        $list_of_contact_objects['Work']['fax'],
                                                        $VCard_object->vcard_tel('vcard:Work', 'vcard:Fax', true)));
            $list_of_contact_objects['Work']['email'] = array_unique(array_merge(
                                                        $list_of_contact_objects['Work']['email'],
                                                        $VCard_object->vcard_email('vcard:Work', 'vcard:Email', true) /* not valid by current Vcard model */,
                                                        $VCard_object->vcard_workEmail));
            $list_of_contact_objects['Home']['adr'] = array_unique(array_merge(
                                                        $list_of_contact_objects['Home']['adr'],
                                                        $VCard_object->vcard_adr('vcard:Home')));
            $list_of_contact_objects['Home']['tel'] = array_unique(array_merge(
                                                        $list_of_contact_objects['Home']['tel'],
                                                        $VCard_object->vcard_tel('vcard:Home', '-vcard:Fax')));
            $list_of_contact_objects['Home']['fax'] = array_unique(array_merge(
                                                        $list_of_contact_objects['Home']['fax'],
                                                        $VCard_object->vcard_tel('vcard:Home', 'vcard:Fax', true)));
            $list_of_contact_objects['Home']['email'] = array_unique(array_merge(
                                                        $list_of_contact_objects['Home']['email'],
                                                        $VCard_object->vcard_email('vcard:Home', 'vcard:Email', true) /* not valid by current Vcard model */,
                                                        $VCard_object->vcard_personalEmail));
            
            unset($VCard_object);
        }
        
        $this->content->number_of_contacts = count($list_of_VCard_objects);
        unset($list_of_VCard_objects);
        
        $list_of_VCard_places = array('Home','Work');
        $list_of_contact_attributes = array('tel'=>'Phone', 'fax'=>'Fax', 'email'=>'Email');
        
        foreach ($list_of_VCard_places as $VCard_place)
        {
            foreach ($list_of_contact_objects[$VCard_place]['adr'] as $adr_object)
            {
                if ($extended_address = $adr_object->getLiteral(array('vcard_extended-address')))
                {
                    $list_of_contacts[$VCard_place]['Address'][] = $extended_address;
                }
                else
                {
                    $adrparts = array();
                    $adrparts[] = ($pobox = $adr_object->getLiteral(array('vcard_post-office-box'))) ? 'P.O.Box '.$pobox : $adr_object->getLiteral(array('vcard_street-address'));
                    if ($postalcode = $adr_object->getLiteral(array('vcard_postal-code'))) $adrparts[] = $postalcode;
                    if ($locality = $adr_object->getLiteral(array('vcard_locality'))) $adrparts[] = $locality;
                    if ($region = $adr_object->getLiteral(array('vcard_region'))) $adrparts[] = $region;
                    if ($country = $adr_object->getLiteral(array('vcard_country-name'))) $adrparts[] = $country;
                    $list_of_contacts[$VCard_place]['Address'][] = implode(', ', $adrparts);
                }
                
                unset($adr_object);
            }
            
            foreach($list_of_contact_attributes as $contact_attribute => $contact_attribute_label)
            {
                foreach ($list_of_contact_objects[$VCard_place][$contact_attribute] as $$contact_attribute)
                {
                    if (is_object($$contact_attribute))
                    {
                        $list_of_contacts[$VCard_place][$contact_attribute_label][] = array(
                            'link' => $$contact_attribute->uri,
                            'label' =>($label_temp = $$contact_attribute->getLiteral(array('rdf_value', 'rdfs_label')))?$label_temp:$$contact_attribute->uri
                        );
                        unset($label_temp);
                    }
                    unset($$contact_attribute);
                }
                
                unset($contact_attribute);
                unset($contact_attribute_label);
            }
            
            unset($VCard_place);
            
        }
        
        unset($list_of_VCard_places);
        unset($list_of_contact_attributes);
        unset($list_of_contact_objects);
        
        $this->content->list_of_contacts = $list_of_contacts;
        unset($list_of_contacts);

        // -- Network ----------------------------------------------------------
        
        $list_of_known_persons = array();
        $list_of_person_objects = array_unique(array_merge(
                                    $this->RESOURCE->rel_closeFriendOf,
                                    $this->RESOURCE->rel_acquaintanceOf,
                                    $this->RESOURCE->rel_colleagueOf,
                                    $this->RESOURCE->rel_worksWith,
                                    $this->RESOURCE->foaf_knows)/*, SORT_REGULAR*/);
        
        
        if ($list_of_person_objects)
        {
            foreach ($list_of_person_objects as $person_object)
            {
                if (is_object($person_object) && $person_name = $person_object->getLiteral(array('foaf_name', 'foaf_nick', 'rdfs_label', 'dc_title')))
                {
                    $person_info = array(
                        'name_or_nick' => $person_name,
                        'homepage_link' => null, 'homepage_label' => null,
                        'weblog_link' => null, 'weblog_label' => null,
                        'resource_link' => null, 'resource_label' => $person_name.' (FOAF)',
                        'depiction' =>'<span class="nodepiction">&nbsp;</span>'
                    );

                    if (($person_homepage = $person_object->foaf_homepage) && isset($person_homepage[0]))
                    {
                        $person_info['homepage_link'] = is_object($person_homepage[0])?$person_homepage[0]->uri:$person_homepage[0];
                        $person_info['homepage_label'] = (is_object($person_homepage[0]) && $homepage_label=$person_homepage[0]->getLiteral(array('rdfs_label', 'dc_title')))?$homepage_label:'Homepage';
                    
                        unset($person_homepage);
                    }
                    
                    if (($person_weblog = $person_object->foaf_weblog) && isset($person_weblog[0]))
                    {
                        $person_info['weblog_link'] = is_object($person_weblog[0])?$person_weblog[0]->uri:$person_weblog[0];
                        $person_info['weblog_name'] = (is_object($person_weblog[0]) && $weblog_label=$person_weblog[0]->getLiteral(array('rdfs_label', 'dc_title')))?$weblog_label:'Weblog';
                    
                        unset($person_weblog);
                    }
                    
                    if (substr($person_object->uri, 0, 7) == 'http://')
                    {
                        $person_info['resource_link'] = $person_object->uri;
                    }
                    
                    if ($person_depiction = $person_object->getImage())
                    {
                        $person_info['depiction'] = $person_depiction;
                        unset($person_depiction);
                    }
                    
                    $list_of_known_persons[] = $person_info;
                    
                    unset($person_name);                    
                    unset($person_info);
                }
                unset($person_object);
            }
        }
        
        unset($list_of_person_objects);
        
        $this->content->list_of_known_persons = $list_of_known_persons;
        unset($list_of_known_persons);
        
        // -- Debug log --------------------------------------------------------
        
        $this->content->debug_log = $this->RESOURCE->logUsage;

        // -- Fallback for older Foafpress version without controller level ----
        // $this->content->FP = $this->RESOURCE;
        
        return;
                                    
    }
}
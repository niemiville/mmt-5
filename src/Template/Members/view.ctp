
<div class="members view large-8 medium-16 columns content float: left">
    <h3><?= h($member->user->first_name . " ". $member->user->last_name) ?></h3>
    <?php
        // Edit link not visible to devs or managers
        $admin = $this->request->session()->read('is_admin');
        $supervisor = ( $this->request->session()->read('selected_project_role') == 'supervisor' ) ? 1 : 0;
        if ($admin || $supervisor ) { ?>
            <button id="navbutton"><?= $this->Html->link(__('Edit Member'), ['action' => 'edit', $member->id]) ?> </button>
        <?php } 
        // if member has workinghours and member's role is dev or manager
        if (($member->project_role == 'developer') || ($member->project_role == 'manager')) { 
            if (!empty($member->workinghours)) {?>
            <button id="navbutton"><?= $this->Html->link(__('Member\'s logged tasks'), ['controller' => 'Workinghours', 'action' => 'tasks', $member->id]) ?> </button>
        <?php } 
        } ?>
    <div class="member-table">
        <div class="member-cell">
                <table class="vertical-table">
                    <tr>
                        <th><?= __('Project Role') ?></th>
                        <td><?= h($member->project_role) ?></td>
                    </tr>
                    <tr>
                        <th><?= __('Starting Date') ?></th>
                        <td><?php 
                                    if ($member->starting_date != NULL)
                                            echo h($member->starting_date->format('d.m.Y')); 
                                    ?></td>
                    </tr>
                    <tr>
                        <th><?= __('Ending Date') ?></th>
                        <td><?php 
                                    if ($member->ending_date != NULL)
                                            echo h($member->ending_date->format('d.m.Y')); 
                                    ?></td>
                    </tr>
                    <tr>
                        <?php

                        // Removed link from the email address 
                        ?>
                        <th><?= __('Email') ?></th>
                        <td><?= $member->user->email ?></td>

                    </tr>

                    <tr>
                        <th><?= __('Data usage in research') ?></th>
                        <td>
                        <?php
                            $id_length = ceil(log10(abs($this->request->session()->read('selected_project_memberid') + 1)));
                            if ($admin || $supervisor || $this->request->session()->read('selected_project_memberid') == substr($this->request->url, -$id_length))
                                if ($member->user->research_allowed == 1){
                                    echo ("Allowed");
                                } else if ($member->user->research_allowed == 0){
                                    echo ("Disallowed");
                                } else if ($member->user->research_allowed == -1){
                                    echo ("No answer");
                                } else {
                                    echo ("No answer");
                                }
                                ?></td>
                    </tr>
                    
                </table>
            </div>
    </div>
    
    <div class="related">
        <?php if (!empty($member->workinghours)): ?>
        <h4><?= __('Working hours') ?></h4>
            <table cellpadding="0" cellspacing="0">

                <tr>
                    <th><?= __('Worktype') ?></th>
                    <th><?= __('Hours') ?></th>
                </tr>

                <?php         
                $query = $member->workinghours;
                $memberID = $member->id;
               
                foreach ($query as $temp) {
                   $hours[] = $temp->duration;
                   $sum = array_sum($hours);  
                }
                // Fill array with zeros to avoid a bug if there are no workinghours of some work type
                $sums = array();
                $sums = array_fill(1, 5, 0);
                $id = 0;
                foreach($query as $temp) {
                    $hour = 0;
                    if ($temp->worktype_id === 1) {
                        $hour = $temp->duration;
                        if (!(isset($sums[1]))) {
                            $sums[1] = $hour;
                        }
                        else {
                            $sums[1] += $hour;
                        }
                    }
                    if ($temp->worktype_id === 2) {
                        $hour = $temp->duration;
                        if (!(isset($sums[2]))) {
                            $sums[2] = $hour;
                        }
                        else {
                            $sums[2] += $hour;
                        }
                    }
                    if ($temp->worktype_id === 3) {
                        $hour = $temp->duration;
                        if (!(isset($sums[3]))) {
                            $sums[3] = $hour;
                        }
                        else {
                            $sums[3] += $hour;
                        } 
                    }
                    if ($temp->worktype_id === 4) {
                        $hour = $temp->duration;
                        if (!(isset($sums[4]))) {
                            $sums[4] = $hour;
                        }
                        else {
                            $sums[4] += $hour;
                        }
                    }
                    if ($temp->worktype_id === 5) {
                        $hour = $temp->duration;
                        if (!(isset($sums[5]))) {
                            $sums[5] = $hour;
                        }
                        else {
                            $sums[5] += $hour;
                        }
                    }                  
                }          
                // Get the names for worktypes
              	$queryForTypes = Cake\ORM\TableRegistry::get('Worktypes')
                    ->find()
                    ->toArray();
                ?>

                <?php             
                foreach($queryForTypes as $type): ?>
                <tr>
                    <td><?= h($type->description) ?></td>                
                    <td><?= h($sums[$type->id]) ?></td>

                <?php endforeach; ?>   
                </tr>
                <tr style="border-top: 2px solid black;">
                    <td><b><?= __('Total') ?></b></td> 
                    <td><b><?= h($sum) ?></b></td>
                </tr>    
            </table>
        <?php endif; ?>          
    </div>   
</div>

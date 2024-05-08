<?php
if (!defined('OSTSCPINC') || !$thisstaff
        || !$thisstaff->hasPerm(Ticket::PERM_CREATE, false))
        die('Access Denied');

$info=array();
$info=Format::htmlchars(($errors && $_POST)?$_POST:$info, true);

if ($_SESSION[':form-data'] && !$_GET['tid'])
  unset($_SESSION[':form-data']);

//  Use thread entry to seed the ticket
if (!$user && $_GET['tid'] && ($entry = ThreadEntry::lookup($_GET['tid']))) {
    if ($entry->getThread()->getObjectType() == 'T')
      $oldTicketId = $entry->getThread()->getObjectId();
    if ($entry->getThread()->getObjectType() == 'A')
      $oldTaskId = $entry->getThread()->getObjectId();

    $_SESSION[':form-data']['message'] = Format::htmlchars($entry->getBody());
    $_SESSION[':form-data']['ticketId'] = $oldTicketId;
    $_SESSION[':form-data']['taskId'] = $oldTaskId;
    $_SESSION[':form-data']['eid'] = $entry->getId();
    $_SESSION[':form-data']['timestamp'] = $entry->getCreateDate();

    if ($entry->user_id)
       $user = User::lookup($entry->user_id);

     if (($m= TicketForm::getInstance()->getField('message'))) {
         $k = 'attach:'.$m->getId();
         unset($_SESSION[':form-data'][$k]);
        foreach ($entry->getAttachments() as $a) {
          if (!$a->inline && $a->file) {
            $_SESSION[':form-data'][$k][$a->file->getId()] = $a->getFilename();
            $_SESSION[':uploadedFiles'][$a->file->getId()] = $a->getFilename();
          }
        }
     }
}

if (!$info['topicId'])
    $info['topicId'] = $cfg->getDefaultTopicId();

$forms = array();
if ($info['topicId'] && ($topic=Topic::lookup($info['topicId']))) {
    foreach ($topic->getForms() as $F) {
        if (!$F->hasAnyVisibleFields())
            continue;
        if ($_POST) {
            $F = $F->instanciate();
            $F->isValidForClient();
        }
        $forms[] = $F;
    }
}

if ($_POST)
    $info['duedate'] = Format::date(strtotime($info['duedate']), false, false, 'UTC');
?>
<form action="tickets.php?a=open" method="post" class="save"  enctype="multipart/form-data">
 <?php csrf_token(); ?>
 <input type="hidden" name="do" value="create">
 <input type="hidden" name="a" value="open">
<div style="margin-bottom:20px; padding-top:5px;">
    <div class="pull-left flush-left">
        <h2><?php echo __('Open a New Ticket');?></h2>
    </div>
</div>
 <table class="form_table fixed" width="940" border="0" cellspacing="0" cellpadding="2">
    <thead>
    <!-- This looks empty - but beware, with fixed table layout, the user
         agent will usually only consult the cells in the first row to
         construct the column widths of the entire toable. Therefore, the
         first row needs to have two cells -->
        <tr><td style="padding:0;"></td><td style="padding:0;"></td></tr>
    </thead>
    <tbody>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('User and Collaborators'); ?></strong>: </em>
                <div class="error"><?php echo $errors['user']; ?></div>
            </th>
        </tr>
              <?php
              if ($user) { ?>
                  <tr><td><?php echo __('User'); ?>:</td><td>
                    <div id="user-info">
                      <input type="hidden" name="uid" id="uid" value="<?php echo $user->getId(); ?>" />
                      <?php if ($thisstaff->hasPerm(User::PERM_EDIT)) { ?>
                      <a href="#" onclick="javascript:
                      $.userLookup('ajax.php/users/<?php echo $user->getId(); ?>/edit',
                      function (user) {
                        $('#user-name').text(user.name);
                        $('#user-email').text(user.email);
                      });
                      return false;
                      ">
                      <?php } else { ?>
                      <a href="#">
                      <?php } ?>
                      <i class="icon-user"></i>
                      <span id="user-name"><?php echo Format::htmlchars($user->getName()); ?></span>
                      &lt;<span id="user-email"><?php echo $user->getEmail(); ?></span>&gt;
                    </a>
                    <a class="inline button" style="overflow:inherit" href="#"
                    onclick="javascript:
                    $.userLookup('ajax.php/users/select/'+$('input#uid').val(),
                    function(user) {
                      $('input#uid').val(user.id);
                      $('#user-name').text(user.name);
                      $('#user-email').text('<'+user.email+'>');
                    });
                    return false;
                    "><i class="icon-retweet"></i> <?php echo __('Change'); ?></a>
                  </div>
                </td>
              </tr>
              <?php
            } else { //Fallback: Just ask for email and name
              ?>
              <tr id="userRow">
                <td width="120"><?php echo __('User'); ?>:</td>
                <td>
                  <span>
                    <select class="userSelection" name="name" id="user-name"
                    data-placeholder="<?php echo __('Select User'); ?>">
                  </select>
                </span>

                <a class="inline button" style="overflow:inherit" href="#"
                onclick="javascript:
                $.userLookup('ajax.php/users/lookup/form', function (user) {
                  var newUser = new Option(user.email + ' - ' + user.name, user.id, true, true);
                  return $(&quot;#user-name&quot;).append(newUser).trigger('change');
                });
                return false;
                "><i class="icon-plus"></i> <?php echo __('Add New'); ?></a>

                <span class="error">*</span>
                <br/><span class="error"><?php echo $errors['name']; ?></span>
              </td>
              <div>
                <input type="hidden" size=45 name="email" id="user-email" class="attached"
                placeholder="<?php echo __('User Email'); ?>"
                autocomplete="off" autocorrect="off" value="<?php echo $info['email']; ?>" />
              </div>
            </tr>
            <?php
          } ?>
          <tr id="ccRow">
            <td width="160"><?php echo __('Cc'); ?>:</td>
            <td>
              <span>
                <select class="collabSelections" name="ccs[]" id="cc_users_open" multiple="multiple"
                ref="tags" data-placeholder="<?php echo __('Select Contacts'); ?>">
              </select>
            </span>

            <a class="inline button" style="overflow:inherit" href="#"
            onclick="javascript:
            $.userLookup('ajax.php/users/lookup/form', function (user) {
              var newUser = new Option(user.name, user.id, true, true);
              return $(&quot;#cc_users_open&quot;).append(newUser).trigger('change');
            });
            return false;
            "><i class="icon-plus"></i> <?php echo __('Add New'); ?></a>

            <br/><span class="error"><?php echo $errors['ccs']; ?></span>
          </td>
        </tr>
        <?php
        if ($cfg->notifyONNewStaffTicket()) {
         ?>
        <tr class="no_border">
          <td>
            <?php echo __('Ticket Notice');?>:
          </td>
          <td>
            <select id="reply-to" name="reply-to">
              <option value="all"><?php echo __('Alert All'); ?></option>
              <option value="user"><?php echo __('Alert to User'); ?></option>
              <option value="none">&mdash; <?php echo __('Do Not Send Alert'); ?> &mdash;</option>
            </select>
          </td>
        </tr>
      <?php } ?>
    </tbody>
    <tbody>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Ticket Information and Options');?></strong>:</em>
            </th>
        </tr>
        <tr>
            <td width="160" class="required">
                <?php echo __('Ticket Source');?>:
            </td>
            <td>
                <select name="source">
                    <?php
                    $source = $info['source'] ?: 'Phone';
                    $sources = Ticket::getSources();
                    unset($sources['Web'], $sources['API']);
                    foreach ($sources as $k => $v)
                        echo sprintf('<option value="%s" %s>%s</option>',
                                $k,
                                ($source == $k ) ? 'selected="selected"' : '',
                                $v);
                    ?>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['source']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="160" class="required">
                <?php echo __('Help Topic'); ?>:
            </td>
            <td>
                <select name="topicId" onchange="javascript:
                        var data = $(':input[name]', '#dynamic-form').serialize();
                        $.ajax(
                          'ajax.php/form/help-topic/' + this.value,
                          {
                            data: data,
                            dataType: 'json',
                            success: function(json) {
                              $('#dynamic-form').empty().append(json.html);
                              $(document.head).append(json.media);
                            }
                          });">
                    <?php
                    if ($topics=$thisstaff->getTopicNames(false, false)) {
                        if (count($topics) == 1)
                            $selected = 'selected="selected"';
                        else { ?>
                        <option value="" selected >&mdash; <?php echo __('Select Help Topic'); ?> &mdash;</option>
<?php                   }
                        foreach($topics as $id =>$name) {
                            echo sprintf('<option value="%d" %s %s>%s</option>',
                                $id, ($info['topicId']==$id)?'selected="selected"':'',
                                $selected, $name);
                        }
                        if (count($topics) == 1 && !$forms) {
                            if (($T = Topic::lookup($id)))
                                $forms =  $T->getForms();
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['topicId']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="160">
                <?php echo __('Department'); ?>:
            </td>
            <td>
                <select name="deptId">
                    <option value="" selected >&mdash; <?php echo __('Select Department'); ?>&mdash;</option>
                    <?php
                    if($depts=$thisstaff->getDepartmentNames(true)) {
                        foreach($depts as $id =>$name) {
                            if (!($role = $thisstaff->getRole($id))
                                || !$role->hasPerm(Ticket::PERM_CREATE)
                            ) {
                                // No access to create tickets in this dept
                                continue;
                            }
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['deptId']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error"><?php echo $errors['deptId']; ?></font>
            </td>
        </tr>

         <tr>
            <td width="160">
                <?php echo __('SLA Plan');?>:
            </td>
            <td>
                <select name="slaId">
                    <option value="0" selected="selected" >&mdash; <?php echo __('System Default');?> &mdash;</option>
                    <?php
                    if($slas=SLA::getSLAs()) {
                        foreach($slas as $id =>$name) {
                            echo sprintf('<option value="%d" %s>%s</option>',
                                    $id, ($info['slaId']==$id)?'selected="selected"':'',$name);
                        }
                    }
                    ?>
                </select>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['slaId']; ?></font>
            </td>
         </tr>

         <tr>
            <td width="160">
                <?php echo __('Due Date');?>:
            </td>
            <td>
                <?php
                $duedateField = Ticket::duedateField('duedate', $info['duedate']);
                $duedateField->render();
                ?>
                &nbsp;<font class="error">&nbsp;<?php echo $errors['duedate']; ?> &nbsp; <?php echo $errors['time']; ?></font>
                <em><?php echo __('Time is based on your time
                        zone');?>&nbsp;(<?php echo $cfg->getTimezone($thisstaff); ?>)</em>
            </td>
        </tr>
        
        <?php
        if($thisstaff->hasPerm(Ticket::PERM_ASSIGN, false)) { ?>
        <tr>
            <td width="160"><?php echo __('Assign To');?>:</td>
            <td>
                <select id="assignId" name="assignId">
                    <option value="0" selected="selected">&mdash; <?php echo __('Select an Agent OR a Team');?> &mdash;</option>
                    <?php
                    $users = Staff::getStaffMembers(array(
                                'available' => true,
                                'staff' => $thisstaff,
                                ));
                    if ($users) {
                        echo '<OPTGROUP label="'.sprintf(__('Agents (%d)'), count($users)).'">';
                        foreach ($users as $id => $name) {
                            $k="s$id";
                            echo sprintf('<option value="%s" %s>%s</option>',
                                        $k, (($info['assignId']==$k) ? 'selected="selected"' : ''), $name);
                        }
                        echo '</OPTGROUP>';
                    }

                    if(($teams=Team::getActiveTeams())) {
                        echo '<OPTGROUP label="'.sprintf(__('Teams (%d)'), count($teams)).'">';
                        foreach($teams as $id => $name) {
                            $k="t$id";
                            echo sprintf('<option value="%s" %s>%s</option>',
                                        $k,(($info['assignId']==$k)?'selected="selected"':''),$name);
                        }
                        echo '</OPTGROUP>';
                    }
                    ?>
                </select>&nbsp;<span class='error'>&nbsp;<?php echo $errors['assignId']; ?></span>
            </td>
        </tr>
        <?php } ?>
        </tbody>
        <tbody>
            <tr>
                <th colspan="2">
                    <em><strong><?php echo __('Avarias nas cabines');?></strong>:</em>
                </th>
            </tr>
            <tr>
                <td width="160" class="required"><?php echo __('Distrito');?>:</td>
                <td>
                    <select name="district_option" id="district_option" onchange="updateAddressOptions();">
                        <option value="" selected><?php echo __('-Select District-');?></option>
                        <?php 
                        $districtOptions = FormsPlugin::getDistricts(null);

                        foreach ($districtOptions as $option) {
                            $selected = ($info['district_option'] === $option) ? "selected" : ""; // Check if the option is selected
                            echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
                        }
                        ?>
                    </select>
                    &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['district_option']; ?></font>
                </td>
            </tr>
            <tr>
                <td width="160"><?php echo __('Morada');?>:</td>
                <td>
                    <select name="address_option" id="address_option" onchange="(() => {updateDistrictOptions(); updateCabinOptions();})()">
                        <option value="" selected><?php echo __('-Select Address-');?></option>
                        <?php 
                        $addressOptions = FormsPlugin::getAddresses(null);

                        foreach ($addressOptions as $option) {
                            $selected = ($info['address_option'] === $option) ? "selected" : ""; // Check if the option is selected
                            echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
                        }
                        ?>
                    </select>
                    &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['address_option']; ?></font>
                </td>
            </tr>
            <tr>
                <td width="160"><?php echo __('Cabine');?>:</td>
                <td>
                    <select name="cabinet_option" id="cabinet_option" onchange="updateEquipments();">
                        <option value="" selected><?php echo __('-Select Cabinet-');?></option>
                    </select>
                    &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['cabinet_option']; ?></font>
                </td>
            </tr>
            <tr>
                <td width="160"><?php echo __('Equipamentos Avariados');?>:</td>
                <td>
                    <div id="checkbox_container"></div>
                </td>
            </tr>
        </tbody>
        <tbody id="dynamic-form">
        <?php
            $options = array('mode' => 'create');
            foreach ($forms as $form) {
                print $form->getForm($_SESSION[':form-data'])->getMedia();
                include(STAFFINC_DIR .  'templates/dynamic-form.tmpl.php');
            }
        ?>
        </tbody>
        <tbody>
        <?php
        //is the user allowed to post replies??
        if ($thisstaff->getRole()->hasPerm(Ticket::PERM_REPLY)) { ?>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Response');?></strong>: <?php echo __('Optional response to the above issue.');?></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
            <?php
            if($cfg->isCannedResponseEnabled() && ($cannedResponses=Canned::getCannedResponses())) {
                ?>
                <div style="margin-top:0.3em;margin-bottom:0.5em">
                    <?php echo __('Canned Response');?>:&nbsp;
                    <select id="cannedResp" name="cannedResp">
                        <option value="0" selected="selected">&mdash; <?php echo __('Select a canned response');?> &mdash;</option>
                        <?php
                        foreach($cannedResponses as $id =>$title) {
                            echo sprintf('<option value="%d">%s</option>',$id,$title);
                        }
                        ?>
                    </select>
                    &nbsp;&nbsp;
                    <label class="checkbox inline"><input type='checkbox' value='1' name="append" id="append" checked="checked"><?php echo __('Append');?></label>
                </div>
            <?php
            }
                $signature = '';
                if ($thisstaff->getDefaultSignatureType() == 'mine')
                    $signature = $thisstaff->getSignature(); ?>
                <textarea
                    class="<?php if ($cfg->isRichTextEnabled()) echo 'richtext';
                        ?> draft draft-delete" data-signature="<?php
                        echo Format::viewableImages(Format::htmlchars($signature, true)); ?>"
                    data-signature-field="signature" data-dept-field="deptId"
                    placeholder="<?php echo __('Initial response for the ticket'); ?>"
                    name="response" id="response" cols="21" rows="8"
                    style="width:80%;" <?php
    list($draft, $attrs) = Draft::getDraftAndDataAttrs('ticket.staff.response', false, $info['response']);
    echo $attrs; ?>><?php echo ThreadEntryBody::clean($_POST ? $info['response'] : $draft);
                ?></textarea>
                    <div class="attachments">
<?php
print $response_form->getField('attachments')->render();
?>
                    </div>

                <table border="0" cellspacing="0" cellpadding="2" width="100%">
            <tr>
                <td width="100"><?php echo __('Ticket Status');?>:</td>
                <td>
                    <select name="statusId">
                    <?php
                    $statusId = $info['statusId'] ?: $cfg->getDefaultTicketStatusId();
                    $states = array('open');
                    if ($thisstaff->hasPerm(Ticket::PERM_CLOSE, false))
                        $states = array_merge($states, array('closed'));
                    foreach (TicketStatusList::getStatuses(
                                array('states' => $states)) as $s) {
                        if (!$s->isEnabled()) continue;
                        $selected = ($statusId == $s->getId());
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $s->getId(),
                                $selected
                                 ? 'selected="selected"' : '',
                                __($s->getName()));
                    }
                    ?>
                    </select>
                </td>
            </tr>
             <tr>
                <td width="100"><?php echo __('Signature');?>:</td>
                <td>
                    <?php
                    $info['signature']=$info['signature']?$info['signature']:$thisstaff->getDefaultSignatureType();
                    ?>
                    <label><input type="radio" name="signature" value="none" checked="checked"> <?php echo __('None');?></label>
                    <?php
                    if($thisstaff->getSignature()) { ?>
                        <label><input type="radio" name="signature" value="mine"
                            <?php echo ($info['signature']=='mine')?'checked="checked"':''; ?>> <?php echo __('My Signature');?></label>
                    <?php
                    } ?>
                    <label><input type="radio" name="signature" value="dept"
                        <?php echo ($info['signature']=='dept')?'checked="checked"':''; ?>> <?php echo sprintf(__('Department Signature (%s)'), __('if set')); ?></label>
                </td>
             </tr>
            </table>
            </td>
        </tr>
        <?php
        } //end canPostReply
        ?>
        <tr>
            <th colspan="2">
                <em><strong><?php echo __('Internal Note');?></strong>
                <font class="error">&nbsp;<?php echo $errors['note']; ?></font></em>
            </th>
        </tr>
        <tr>
            <td colspan=2>
                <textarea
                    class="<?php if ($cfg->isRichTextEnabled()) echo 'richtext';
                        ?> draft draft-delete"
                    placeholder="<?php echo __('Optional internal note (recommended on assignment)'); ?>"
                    name="note" cols="21" rows="6" style="width:80%;" <?php
    list($draft, $attrs) = Draft::getDraftAndDataAttrs('ticket.staff.note', false, $info['note']);
    echo $attrs; ?>><?php echo ThreadEntryBody::clean($_POST ? $info['note'] : $draft);
                ?></textarea>
            </td>
        </tr>
    </tbody>
</table>
<p style="text-align:center;">
    <input type="submit" name="submit" value="<?php echo _P('action-button', 'Open');?>">
    <input type="reset"  name="reset"  value="<?php echo __('Reset');?>">
    <input type="button" name="cancel" value="<?php echo __('Cancel');?>" onclick="javascript:
        $(this.form).find('textarea.richtext')
          .redactor('plugin.draft.deleteDraft');
        window.location.href='tickets.php'; " />
</p>
</form>
<script type="text/javascript">
$(function() {
    $('input#user-email').typeahead({
        source: function (typeahead, query) {
            $.ajax({
                url: "ajax.php/users?q="+query,
                dataType: 'json',
                success: function (data) {
                    typeahead.process(data);
                }
            });
        },
        onselect: function (obj) {
            $('#uid').val(obj.id);
            $('#user-name').val(obj.name);
            $('#user-email').val(obj.email);
        },
        property: "/bin/true"
    });

   <?php
    // Popup user lookup on the initial page load (not post) if we don't have a
    // user selected
    if (!$_POST && !$user) {?>
    setTimeout(function() {
      $.userLookup('ajax.php/users/lookup/form', function (user) {
        window.location.href = window.location.href+'&uid='+user.id;
      });
    }, 100);
    <?php
    } ?>
});

$(function() {
    $('a#editorg').click( function(e) {
        e.preventDefault();
        $('div#org-profile').hide();
        $('div#org-form').fadeIn();
        return false;
     });

    $(document).on('click', 'form.org input.cancel', function (e) {
        e.preventDefault();
        $('div#org-form').hide();
        $('div#org-profile').fadeIn();
        return false;
    });

    $('.userSelection').select2({
      width: '450px',
      minimumInputLength: 3,
      ajax: {
        url: "ajax.php/users/local",
        dataType: 'json',
        data: function (params) {
          return {
            q: params.term,
          };
        },
        processResults: function (data) {
          return {
            results: $.map(data, function (item) {
              return {
                text: item.email + ' - ' + item.name,
                slug: item.slug,
                email: item.email,
                id: item.id
              }
            })
          };
          $('#user-email').val(item.name);
        }
      }
    });

    $('.userSelection').on('select2:select', function (e) {
      var data = e.params.data;
      $('#user-email').val(data.email);
    });

    $('.userSelection').on("change", function (e) {
      var data = $('.userSelection').select2('data');
      var data = data[0].text;
      var email = data.substr(0,data.indexOf(' '));
      $('#user-email').val(data.substr(0,data.indexOf(' ')));
     });

    $('.collabSelections').select2({
      width: '450px',
      minimumInputLength: 3,
      ajax: {
        url: "ajax.php/users/local",
        dataType: 'json',
        data: function (params) {
          return {
            q: params.term,
          };
        },
        processResults: function (data) {
          return {
            results: $.map(data, function (item) {
              return {
                text: item.name,
                slug: item.slug,
                id: item.id
              }
            })
          };
        }
      }
    });

  });
</script>

<script>
// Define the updateAddressOptions function to fetch and populate address options based on the selected district
function updateAddressOptions() {
    var selectedDistrict = document.getElementById("district_option").value;
    var addressCombobox = document.getElementById("address_option");
    // Store the currently selected address before updating options
    var selectedAddress = addressCombobox.value;
    
    // Clear existing options
    addressCombobox.innerHTML = "";

    // Fetch addresses for the selected district via AJAX
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var addresses = JSON.parse(this.responseText);

            var defaultOption = document.createElement("option");
            defaultOption.value = ""; // Set default value
            defaultOption.text = "-Select Address-"; // Set default text
            addressCombobox.add(defaultOption);

            // Populate options for the address combobox
            addresses.forEach(function(address) {
                var option = document.createElement("option");
                option.value = address;
                option.text = address;
                addressCombobox.add(option);
            });

            // Keep the selected address if it's not a default option
            if (selectedAddress && addresses.includes(selectedAddress)) {
                addressCombobox.value = selectedAddress;
            }
        }
    };
    var url = "get_addresses.php?district=" + encodeURIComponent(selectedDistrict);
    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}

// Define the updateDistrictOptions function to fetch and populate district options based on the selected address
function updateDistrictOptions() {
    var selectedAddress = document.getElementById("address_option").value;
    var districtCombobox = document.getElementById("district_option");  
    // Store the currently selected district before updating options
    var selectedDistrict = districtCombobox.value;
    
    // Clear existing options
    districtCombobox.innerHTML = "";

    // Fetch addresses for the selected district via AJAX
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            var districts = JSON.parse(this.responseText);

            var defaultOption = document.createElement("option");
            defaultOption.value = ""; // Set default value
            defaultOption.text = "-Select District-"; // Set default text
            districtCombobox.add(defaultOption);

            // Populate options for the address combobox
            districts.forEach(function(district) {
                var option = document.createElement("option");
                option.value = district;
                option.text = district;
                districtCombobox.add(option);
            });

            // Keep the selected district if it's not a default option
            if (selectedDistrict && districts.includes(selectedDistrict)) {
                districtCombobox.value = selectedDistrict;
            }
        }
    };
    var url = "get_districts.php?address=" + encodeURIComponent(selectedAddress);
    xmlhttp.open("GET", url, true);
    xmlhttp.send();
}
// Define the updateCabinOptions function to fetch and populate cabinet options based on the selected district and address
function updateCabinOptions() {
    var selectedAddress = document.getElementById("address_option").value;
    var cabinetCombobox = document.getElementById("cabinet_option");
    
    // Clear existing options
    cabinetCombobox.innerHTML = "";

    // Check if either district or address has a non-default value
    if (selectedAddress !== "") {
        // Fetch cabinets only if either district or address is selected
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var cabinets = JSON.parse(this.responseText);

                // Populate options for the cabinet combobox
                var defaultOption = document.createElement("option");
                defaultOption.value = ""; // Set default value
                defaultOption.text = "-Select Cabinet-"; // Set default text
                cabinetCombobox.add(defaultOption);

                cabinets.forEach(function(cabinet) {
                    var option = document.createElement("option");
                    option.value = cabinet;
                    option.text = cabinet;
                    cabinetCombobox.add(option);
                });
            }
        };

        // Fetch cabinets based on the selected district and address
        var url = "get_cabinets.php?address=" + encodeURIComponent(selectedAddress);
        xmlhttp.open("GET", url, true);
        xmlhttp.send();
    } else {
        // If both district and address are default, reset cabinet combobox to default state
        var defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.text = "-Select Cabinet-";
        cabinetCombobox.add(defaultOption);
    }
}

function updateEquipments() {
    var selectedCabinet = document.getElementById("cabinet_option").value;
    var checkboxContainer = document.getElementById("checkbox_container");
    checkboxContainer.innerHTML = "";
     
    // If a cabinet is selected, fetch checkbox values from the server
    if (selectedCabinet !== "") {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                var checkboxValues = JSON.parse(this.responseText);
                var equipments = ['Cinemómetro','Router','UPS'];
                // Create checkboxes based on fetched values
                checkboxValues.forEach(function(checkboxValue, index) {
                    var checkboxDiv = document.createElement("div");
                    checkboxDiv.className = "checkbox-item";
                    
                    // Create checkbox label with text in bold
                    var label = document.createElement("label");
                    label.innerHTML = "<strong>" + equipments[index] +": </strong>" + checkboxValue;
                    checkboxDiv.appendChild(label);
                    
                    var checkbox = document.createElement("input");
                    checkbox.type = "checkbox";
                    checkbox.name = "checkbox_name[]";
                    checkbox.value = checkboxValue;
                    checkboxDiv.appendChild(checkbox);
                    
                    checkboxContainer.appendChild(checkboxDiv);
                });
            }
        };

        // Fetch checkbox values from the server based on the selected cabinet
        var url = "get_checkbox_values.php?cabinet=" + encodeURIComponent(selectedCabinet);
        xmlhttp.open("GET", url, true);
        xmlhttp.send();
    }
}
</script>

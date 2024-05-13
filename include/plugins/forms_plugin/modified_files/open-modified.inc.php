<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');
$info=array();
if($thisclient && $thisclient->isValid()) {
    $info=array('name'=>$thisclient->getName(),
                'email'=>$thisclient->getEmail(),
                'phone'=>$thisclient->getPhoneNumber());
}

$info=($_POST && $errors)?Format::htmlchars($_POST):$info;

$form = null;
if (!$info['topicId']) {
    if (array_key_exists('topicId',$_GET) && preg_match('/^\d+$/',$_GET['topicId']) && Topic::lookup($_GET['topicId']))
        $info['topicId'] = intval($_GET['topicId']);
    else
        $info['topicId'] = $cfg->getDefaultTopicId();
}

$forms = array();
if ($info['topicId'] && ($topic=Topic::lookup($info['topicId']))) {
    foreach ($topic->getForms() as $F) {
        if (!$F->hasAnyVisibleFields())
            continue;
        if ($_POST) {
            $F = $F->instanciate();
            $F->isValidForClient();
        }
        $forms[] = $F->getForm();
    }
}

?>
<h1><?php echo __('Open a New Ticket');?></h1>
<p><?php echo __('Please fill in the form below to open a new ticket.');?></p>
<form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
  <?php csrf_token(); ?>
  <input type="hidden" name="a" value="open">
  <table width="800" cellpadding="1" cellspacing="0" border="0">
    <tbody>
<?php
        if (!$thisclient) {
            $uform = UserForm::getUserForm()->getForm($_POST);
            if ($_POST) $uform->isValid();
            $uform->render(array('staff' => false, 'mode' => 'create'));
        }
        else { ?>
            <tr><td colspan="2"><hr /></td></tr>
        <tr><td><?php echo __('Email'); ?>:</td><td><?php
            echo $thisclient->getEmail(); ?></td></tr>
        <tr><td><?php echo __('Client'); ?>:</td><td><?php
            echo Format::htmlchars($thisclient->getName()); ?></td></tr>
        <?php } ?>
    </tbody>
    <tbody>
    <tr><td colspan="2"><hr />
        <div class="form-header" style="margin-bottom:0.5em">
        <b><?php echo __('Help Topic'); ?></b>
        </div>
    </td></tr>
    <tr>
        <td colspan="2">
            <select id="topicId" name="topicId" onchange="javascript:
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
                <option value="" selected="selected">&mdash; <?php echo __('Select a Help Topic');?> &mdash;</option>
                <?php
                if($topics=Topic::getPublicHelpTopics()) {
                    foreach($topics as $id =>$name) {
                        echo sprintf('<option value="%d" %s>%s</option>',
                                $id, ($info['topicId']==$id)?'selected="selected"':'', $name);
                    }
                } ?>
            </select>
            <font class="error">*&nbsp;<?php echo $errors['topicId']; ?></font>
        </td>
    </tr>
    </tbody>
    <tbody>
        <tr><td colspan="2"><hr />
            <div class="form-header" style="margin-bottom:0.5em">
            <b><?php echo __('Avaria nas Cabines'); ?></b>
            </div>
        </td></tr>
        <tr>
            <td width="160" class="required"><?php echo __('Distrito');?>:</td>
        </tr> 
        <tr>
            <td colspan="2">
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
            <td width="160" class="required"><?php echo __('Morada');?>:</td>
        </tr> 
        <tr>
            <td colspan="2">
                <select name="address_option" id="address_option" onchange="updateCabinOptions();">
                    <option value="" selected><?php echo __('-Select Address-');?></option>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['address_option']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="160" class="required"><?php echo __('Cabine');?>:</td>
        </tr> 
        <tr>
            <td colspan="2">
                <select name="cabinet_option" id="cabinet_option" onchange="updateEquipments();">
                    <option value="" selected><?php echo __('-Select Cabinet-');?></option>
                </select>
                &nbsp;<font class="error"><b>*</b>&nbsp;<?php echo $errors['cabinet_option']; ?></font>
            </td>
        </tr>
        <tr>
            <td width="160"><?php echo __('Equipamentos Avariados');?>:</td>
        </tr>
        <tr>
            <td>
                <div id="checkbox_container"></div>
            </td>
        </tr>
    </tbody>
    <tbody id="dynamic-form">
        <?php
        $options = array('mode' => 'create');
        foreach ($forms as $form) {
            include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
        } ?>
    </tbody>
    <tbody>
    <?php
    if($cfg && $cfg->isCaptchaEnabled() && (!$thisclient || !$thisclient->isValid())) {
        if($_POST && $errors && !$errors['captcha'])
            $errors['captcha']=__('Please re-enter the text again');
        ?>
    <tr class="captchaRow">
        <td class="required"><?php echo __('CAPTCHA Text');?>:</td>
        <td>
            <span class="captcha"><img src="captcha.php" border="0" align="left"></span>
            &nbsp;&nbsp;
            <input id="captcha" type="text" name="captcha" size="6" autocomplete="off">
            <em><?php echo __('Enter the text shown on the image.');?></em>
            <font class="error">*&nbsp;<?php echo $errors['captcha']; ?></font>
        </td>
    </tr>
    <?php
    } ?>
    <tr><td colspan=2>&nbsp;</td></tr>
    </tbody>
  </table>
<hr/>
  <p class="buttons" style="text-align:center;">
        <input type="submit" value="<?php echo __('Create Ticket');?>">
        <input type="reset" name="reset" value="<?php echo __('Reset');?>">
        <input type="button" name="cancel" value="<?php echo __('Cancel'); ?>" onclick="javascript:
            $('.richtext').each(function() {
                var redactor = $(this).data('redactor');
                if (redactor && redactor.opts.draftDelete)
                    redactor.plugin.draft.deleteDraft();
            });
            window.location.href='index.php';">
  </p>
</form>

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
    var url = "scp/get_addresses.php?district=" + encodeURIComponent(selectedDistrict);
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
        var url = "scp/get_cabinets.php?address=" + encodeURIComponent(selectedAddress);
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
                if(checkboxValues.length != equipments.length) {
                    equipments = ['Router','UPS'];
                }
                
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
                    checkbox.value = equipments[index] + ": " + checkboxValue;
                    checkboxDiv.appendChild(checkbox);
                    
                    checkboxContainer.appendChild(checkboxDiv);
                });
                
                    var checkboxDiv = document.createElement("div");
                    checkboxDiv.className = "checkbox-item";
                    
                    // Create checkbox label with text in bold
                    var label = document.createElement("label");
                    label.innerHTML = "<strong>Outro</strong>";
                    checkboxDiv.appendChild(label);
                    
                    var checkbox = document.createElement("input");
                    checkbox.type = "checkbox";
                    checkbox.name = "checkbox_name[]";
                    checkbox.value = "Outro";
                    checkboxDiv.appendChild(checkbox);
                    
                    checkboxContainer.appendChild(checkboxDiv);
            }
        };

        // Fetch checkbox values from the server based on the selected cabinet
        var url = "scp/get_checkbox_values.php?cabinet=" + encodeURIComponent(selectedCabinet);
        xmlhttp.open("GET", url, true);
        xmlhttp.send();
    } 
}

window.onload = function() {
    var districtOption = document.getElementById("district_option");
    districtOption.value = "";
};
</script>

<?php

require_once 'Jb.class.php';
include_once(dirname(__FILE__) . '/../../classes/MySQL.php');

$jb = new Jb();

if (@$_POST['getsubcategory']) {
  $level = $_POST['level'];
  $level++;

  $cat = $jb->getCategories($_POST['getsubcategory']);

  $html = '<span level="' . $level . '" class="levels level_' . $level . '"><select id="select_category_' . $_POST['getsubcategory'] . '">
            ' . $jb->buildCategoriesHtml($cat) . '
            </select>
            <script type="text/javascript">
             $(document).ready(function(){
               $("#select_category_' . $_POST['getsubcategory'] . '").live("change", function(event){
                 if ($(this).val() != "")
                 {
                   $("#category").val($(this).val());
                   $.ajax({
                      url: \'/modules/jballegro/ajax.php\',
                      data: \'&level=' . $level . '&getsubcategory=\' + $(this).val(),
                      type: "POST",
                      success: function(data) {
                        $(".levels").each(function(index) {
                            if($(this).attr("level")>' . $level . ') $(this).remove();
                        });
                        $(\'#categories_select\').append(data);
                      }
                   });
                 }
               });
             });
     </script></span>';

  echo $html;
}
else if (isset($_POST['getproduct'])) {
  $product = $jb->getProduct($_POST['getproduct']);
  echo json_encode($product);
}
else if (isset($_POST['closeauction'])) {
  echo json_encode($jb->closeAuction($_POST['closeauction']));
}
?>

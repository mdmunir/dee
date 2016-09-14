<?php

/* @var $this \dee\base\View */

$js = <<<JS
    $('#click-me').click(function(){
        alert('Hello...');
    });
JS;
$this->registerJs($js);
$this->title = 'Contoh JS';
?>
<div>
    <button id="click-me">Click Me</button>
</div>
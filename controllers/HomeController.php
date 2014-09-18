<?php
class HomeController extends Controller {
    function process(Request $request = null) {
        echo 'Это ' . __CLASS__;
        
        $tpl = MarkerTpl::get('test.tpl');
        echo $tpl->process([
        	'name' => $request->getParm('name')
        ]);
    }
}
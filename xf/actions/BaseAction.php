<?php
namespace xf\actions;

class BaseAction {
    public function handle($params) {
        $app = \Dataface_Application::getInstance();
        $res = null;
        try {
            $res = $this->handleImpl($params);

        } catch (\Exception $ex) {
            if ($ex->getCode() === 401) {
                return \Dataface_Error::permissionDenied('Permission is denied');
            }

            $res = array(
                'code' => 500,
                'errorCode' => $ex->getCode(),
                'errorMessage' => $ex->getMessage(),
                'message' => 'Publish attempt failed'
            );
        }
        header('Content-type: application/json; charset="'.$app->_conf['oe'].'"');
        echo json_encode($res);
    }  

    public function handleImpl($params) {

    }
}
<?php
namespace IMSGlobal\LTI;

interface Message_Validator {
    public function validate($jwt_body);
    public function can_validate($jwt_body);
}
?>
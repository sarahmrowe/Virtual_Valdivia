<?php
$ERRORS = array();
function add_error($name, $code, $message, $hide = false)
{
    global $ERRORS;
    $ERRORS[$name] = array( "code" => $code,
                            "message" => $message,
                            "hide_from_user" => $hide);
}

add_error("missing_args", 1, "Missing necessary arguments for operation. Please consult the API documentation.");
add_error("action_or_target_invalid", 2, "Please specify a valid action and target.");
add_error("api_function_not_defined", 3, "This target/action combination has no defined responder.");
add_error("query_error", 4, "An error occured in the MySQL query.", true);
add_error("improper_where_clauses", 5, "There appears to be a problem with your 'WHERE' clauses. Please consult the API documentation.");
add_error("token_invalid", 6, "A valid token is required to perform web requests against KORA API. Please consult the API documentation");
?>

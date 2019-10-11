INSERT INTO  `cscart_payment_processors` (
`processor_id` ,
`processor` ,
`processor_script` ,
`processor_template` ,
`admin_template` ,
`callback` ,
`type`
)
VALUES (
'0',  'Netgiro',  'netgiro.php',  'views/orders/components/payments/cc_outside.tpl',  'netgiro.tpl',  'N',  'P'
);
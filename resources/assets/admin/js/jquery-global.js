// En build, jQuery est converti en module CommonJS et ne s'attache plus à window ;
// sb-admin-2.js et nutritrace-admin.js utilisent pourtant le jQuery global.
import * as jquery from 'jquery';

window.jQuery = window.$ = jquery.default ?? window.jQuery;

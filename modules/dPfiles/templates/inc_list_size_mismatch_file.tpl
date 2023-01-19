{{*
 * @package Mediboard\Files
 * @author  SAS OpenXtrem <dev@openxtrem.com>
 * @license https://www.gnu.org/licenses/gpl.html GNU General Public License
 * @license https://www.openxtrem.com/licenses/oxol.html OXOL OpenXtrem Open License
*}}

<table class="main layout">
  <tr>
    <th>{{mb_label object=$file field=_file_path}}</th>
    <th>{{mb_label object=$file field=doc_size}}</th>
    <th>{{mb_label object=$file field=file_real_filename}}</th>
    <th>{{mb_label object=$file field=object_class}}</th>
    <th>{{mb_label object=$file field=object_id}}</th>
  </tr>
  {{foreach from=$size_mismatch_list item=_object}}
    <tr>
      <td>{{$_object->file_path}}</td>
      <td>{{$_object->file_size}}</td>
      <td>{{$_object->file_hash}}</td>
      <td>{{$_object->object_class}}</td>
      <td>{{$_object->object_id}}</td>
    </tr>
  {{/foreach}}
</table>
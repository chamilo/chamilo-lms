Check Extra Fields 'author' and 'company'
======

The "User by organization" report allows the administrator to select a 
date range to show the number of users who have been subscribed to a learning
path or a course during this time frame. The number of users are grouped by 
entity/company.

The "Learning path by author" report allows the administrator to define, for 
each user, if (s)he is an author or not. Then, for each item in a Learning 
Path, the administrator can select who is its author from the identified list
and indicate the cost of that item.

Finally, the reports allow the administrator to select a date range to show
for each author how many of his/her content (LP item) users have been given
access to (based on the learning path subscriptions by users) and show the 
amount of money they should be paid based on the number of accesses given
during this period.

This plugin adds the extra fields necessary to display the reports:

* The "User by organization" report requires the 'company' extra field to be created on user.
* The "Learning path by author" report requires the 'authors' extra field to be created on lp.
* The "LP Item by author" report additional reports requires the 'authorlpitem' extra field to be created on lp_item and the 'authorlp' extra field to be created on 'user'.
* For prices to be adequately shown, the 'price' extra field needs to be created on 'lp_item'.

## Uninstall

When uninstalling this plugin, the extra fields created will not be removed,
for data persistence reasons.

### Optional manual cleanup

The uninstall process preserves extra fields by default. This avoids deleting
reporting metadata that may already be linked to users, learning paths, or LP
items.

If this plugin was only tested and the fields have no values, they can be
removed manually. Check first:

```sql
SELECT ef.id, ef.item_type, ef.variable, COUNT(efv.id) AS values_count
FROM extra_field ef
LEFT JOIN extra_field_values efv ON efv.field_id = ef.id
WHERE
    (ef.item_type = 1 AND ef.variable IN ('company', 'authorlp'))
    OR (ef.item_type = 6 AND ef.variable = 'authors')
    OR (ef.item_type = 7 AND ef.variable IN ('authorlpitem', 'price'))
GROUP BY ef.id, ef.item_type, ef.variable
ORDER BY ef.item_type, ef.variable;
```

Only if all `values_count` results are `0`, remove them with:

```sql
DELETE efv
FROM extra_field_values efv
INNER JOIN extra_field ef ON ef.id = efv.field_id
WHERE
    (ef.item_type = 1 AND ef.variable IN ('company', 'authorlp'))
    OR (ef.item_type = 6 AND ef.variable = 'authors')
    OR (ef.item_type = 7 AND ef.variable IN ('authorlpitem', 'price'));

DELETE efo
FROM extra_field_options efo
INNER JOIN extra_field ef ON ef.id = efo.field_id
WHERE
    (ef.item_type = 1 AND ef.variable IN ('company', 'authorlp'))
    OR (ef.item_type = 6 AND ef.variable = 'authors')
    OR (ef.item_type = 7 AND ef.variable IN ('authorlpitem', 'price'));

DELETE FROM extra_field
WHERE
    (item_type = 1 AND variable IN ('company', 'authorlp'))
    OR (item_type = 6 AND variable = 'authors')
    OR (item_type = 7 AND variable IN ('authorlpitem', 'price'));
```

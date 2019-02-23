## Validators of tree structure catalog in the context of  .htaccess

The following checks are performed:

- lost (hidden) sections catalog Ids which meet the conditions: the section has child, has no parent and it's not the root section
- lost items (if parent sections don't exist)
- ending sections without items  
- zero rdirects in .htaccess ( a => a)
- chain rdirects in .htaccess ( a => b, b => c)
- cycling redirects using recursion in .htaccess ( a => b => ... => a)
- doubles in .htaccess (a => b, a => b)
- multiply targets ID in redirects in .htaccess (a => b, c => b)
- multiply sources ID in redirects in .htaccess (a => b, a => c)
- intersections of Catalog sections IDs and htAccess redirects source IDs (if there is intersections then these sections will be hidden)
- target redirect ids in .htaccess  which doesn't exist in catalog sections

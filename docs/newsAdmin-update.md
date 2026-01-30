# News Admin Module Update Guide

**Date**: 2026-01-30
**Applies to**: WebCreator CMS sites with newsAdmin module

---

## Overview

This document describes improvements made to the newsAdmin module that can be replicated on other WebCreator sites.

### Changes Summary
1. Default category selection (Blog) when adding new news
2. Default date set to today when adding new news
3. Fixed TinyMCE validation requiring double-click to save
4. Added delete confirmation dialog

---

## File Changes

### 1. `framework/modules/newsAdmin/newsAdmin.class.php`

**Location**: `showAddNewsForm()` method (around line 150)

**Change**: Add default category and date assignments after the `assign('tinyMceLanguage','sk')` line:

```php
public function showAddNewsForm() {
    g('view')->addJavascript('tiny_mce/tiny_mce');
    g('view')->addJavascript('jquery.datePicker/jquery-ui-timepicker-addon-0.5.min');
    g('view')->setTemplate('newsAdminEdit');
    g('view')->assign('addNews',1);
    g('view')->assign('tinyMceLanguage','sk');

    // ADD THESE LINES:
    // Default to Blog category (id=2) - adjust ID for your site
    g('view')->assign('categorieId', 2);

    // Default to today's date
    g('view')->assign('article', array('start_date' => date('d.m.Y')));

    // ... rest of method continues
```

**Note**: Check your `news_categories` table to find the correct Blog category ID for your site:
```sql
SELECT id, title FROM news_categories WHERE title LIKE '%blog%';
```

---

### 2. `framework/modules/newsAdmin/templates/smarty/newsAdminEdit.tpl.php`

**Location**: `validateForm()` JavaScript function (around line 53)

**Change**: Replace the entire `validateForm()` function with:

```javascript
function validateForm() {
    // Trigger TinyMCE to save content to textareas before validation
    if (typeof tinyMCE !== 'undefined') {
        tinyMCE.triggerSave();
    }

    $(".validation_error").css('display','none');
    var articleTitle = $("input#articleTitle");
    if (articleTitle.val().length == 0) {
        $("label#articleTitleError").css('display', 'inline');
        $("input#articleTitle").focus();
        return false;
    }
    var prologue = $("textarea#prologue");
    // Strip HTML tags to check if there's actual content
    var prologueText = prologue.val().replace(/<[^>]*>/g, '').trim();
    if (prologueText.length == 0) {
        $("label#prologueError").css('display', 'inline');
        $("textarea#prologue").focus();
        return false;
    }
    var content = $("textarea#contentArticle");
    // Strip HTML tags to check if there's actual content
    var contentText = content.val().replace(/<[^>]*>/g, '').trim();
    if (contentText.length == 0) {
        $("label#contentArticleError").css('display', 'inline');
        $("textarea#contentArticle").focus();
        return false;
    }
    return true;
}
```

**Why this fix works**: TinyMCE stores content in its own internal state and doesn't automatically sync to the underlying textarea. The original validation was checking empty textareas while content existed only in TinyMCE. Calling `tinyMCE.triggerSave()` forces the sync before validation runs.

---

### 3. `framework/modules/newsAdmin/templates/smarty/newsAdmin.tpl.php`

**Location**: Delete button in the articles table (around line 121)

**Change**: Add `onclick` confirmation to the delete link:

**Before**:
```html
<td><a href="{$smarty.const.URL_ROOT}admin/newsAdmin/deleteNews?id={$article.id}" class="delete-button"><img src="{$smarty.const.URL_ROOT}design/images/delete.png" border="0" /></a></td>
```

**After**:
```html
<td><a href="{$smarty.const.URL_ROOT}admin/newsAdmin/deleteNews?id={$article.id}" class="delete-button" onclick="return confirm('Naozaj chcete vymazať túto novinku?');"><img src="{$smarty.const.URL_ROOT}design/images/delete.png" border="0" /></a></td>
```

**Note**: Adjust the confirmation message for your site's language:
- Slovak: `'Naozaj chcete vymazať túto novinku?'`
- English: `'Are you sure you want to delete this news item?'`
- Dutch: `'Weet u zeker dat u dit nieuwsbericht wilt verwijderen?'`

---

## Testing Checklist

After applying changes:

- [ ] Add new news: Blog category should be pre-selected
- [ ] Add new news: Date field should show today's date
- [ ] Add new news with content: Should save on first click (no double-click needed)
- [ ] Delete news: Confirmation dialog should appear
- [ ] Cancel delete: News should remain

---

## Troubleshooting

### Form still requires double-click
- Check that `tinyMCE.triggerSave()` is being called
- Verify the script is not cached (clear browser cache)
- Check browser console for JavaScript errors

### Default category not working
- Verify the category ID matches your database
- Check that `$categorieId` variable is used in the template's select element

### Delete confirmation not appearing
- Check for JavaScript errors in browser console
- Verify the `onclick` attribute is properly formatted with escaped quotes

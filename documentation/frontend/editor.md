# Generic Editor Component

A flexible, reusable code editor component built with Monaco Editor for the Debug Suite WordPress plugin.

## Overview

The `Editor` component is a generic, feature-rich code editor that can be used throughout the application for various editing needs. It's built on top of Monaco Editor (the same editor that powers VS Code) and provides a consistent interface with built-in support for syntax highlighting, themes, and extensive customization options.

## Features

- **Syntax Highlighting**: Automatic language detection from file extensions or explicit language setting
- **Multiple Themes**: Support for light (`vs-light`) and dark (`vs-dark`) themes
- **Loading States**: Built-in loading spinner with customizable text
- **Read-only Mode**: Perfect for code viewing scenarios
- **Change Detection**: Real-time content change callbacks
- **Accessibility**: Full keyboard navigation and screen reader support
- **Responsive**: Automatic layout adjustments
- **TypeScript**: Full TypeScript support with comprehensive type definitions

## Basic Usage

```tsx
import Editor from '@/components/editor';
import { useState } from '@wordpress/element';

const MyComponent = () => {
    const [content, setContent] = useState('// Your code here');

    const handleContentChange = (value: string | undefined) => {
        setContent(value || '');
    };

    return (
        <Editor
            content={content}
            language="javascript"
            onChange={handleContentChange}
            height="400px"
        />
    );
};
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `content` | `string` | Required | The content to display in the editor |
| `language` | `string` | `undefined` | Monaco language for syntax highlighting |
| `filename` | `string` | `undefined` | Filename to infer language from extension |
| `readOnly` | `boolean` | `false` | Whether the editor is read-only |
| `height` | `string` | `"400px"` | Height of the editor |
| `loading` | `boolean` | `false` | Whether the editor is currently loading |
| `className` | `string` | `undefined` | Additional CSS class names |
| `onChange` | `function` | `undefined` | Callback when content changes |
| `onMount` | `function` | `undefined` | Callback when editor is ready |
| `options` | `object` | `{}` | Additional Monaco editor options |
| `showLoadingSpinner` | `boolean` | `true` | Whether to show loading spinner |
| `loadingText` | `string` | `"Loading editor…"` | Text to show during loading |

## Supported Languages

The editor automatically detects language from file extensions:

- **JavaScript/TypeScript**: `.js`, `.ts`, `.jsx`, `.tsx`
- **Python**: `.py`
- **C++**: `.cpp`
- **Web**: `.html`, `.css`
- **Data**: `.json`, `.xml`, `.yml`, `.yaml`
- **Documentation**: `.md`
- **Scripts**: `.sh`
- **Database**: `.sql`
- **Other**: `.java`, `.php`, `.rb`, `.go`, `.cs`, `.swift`

## Advanced Usage Examples

### Read-only Code Viewer

```tsx
<Editor
    content={phpCode}
    filename="wp-config.php"
    readOnly={true}
    height="300px"
    options={{
        theme: 'vs-light',
        minimap: { enabled: false }
    }}
/>
```

### Custom Editor with Loading

```tsx
const [content, setContent] = useState('');
const [loading, setLoading] = useState(true);

// Async content loading
useEffect(() => {
    fetchContent().then(data => {
        setContent(data);
        setLoading(false);
    });
}, []);

return (
    <Editor
        content={content}
        language="markdown"
        loading={loading}
        loadingText="Fetching file content..."
        onChange={(value) => setContent(value || '')}
    />
);
```

### Editor with Custom Key Bindings

```tsx
const handleEditorMount = (editor: any) => {
    // Add Ctrl+S shortcut
    editor.addCommand(editor.KeyMod.CtrlCmd | editor.KeyCode.KeyS, () => {
        handleSave();
    });

    // Add Ctrl+F shortcut for find
    editor.addCommand(editor.KeyMod.CtrlCmd | editor.KeyCode.KeyF, () => {
        editor.getAction('actions.find').run();
    });
};

<Editor
    content={content}
    onMount={handleEditorMount}
    options={{
        automaticLayout: true,
        fontSize: 14,
        lineNumbers: 'on',
        minimap: { enabled: true }
    }}
/>
```

## Monaco Editor Options

The component accepts all standard Monaco Editor options through the `options` prop:

```tsx
<Editor
    content={code}
    options={{
        theme: 'vs-dark',
        fontSize: 16,
        lineNumbers: 'off',
        minimap: { enabled: false },
        wordWrap: 'on',
        automaticLayout: true,
        scrollBeyondLastLine: false,
        folding: true,
        bracketMatching: 'always',
        autoClosingBrackets: 'always',
        autoClosingQuotes: 'always'
    }}
/>
```

## Integration with Debug Suite

The Editor component is designed to work seamlessly with the Debug Suite architecture:

### Service Layer Integration

```tsx
// Use with FileManagerService
const FileContentEditor = () => {
    const [content, setContent] = useState('');
    const [loading, setLoading] = useState(false);
    
    const loadFile = async (filePath: string) => {
        setLoading(true);
        const result = await fileManagerService.getFileContent(filePath);
        
        if (result.isSuccess()) {
            setContent(result.getData().content);
        }
        setLoading(false);
    };

    return (
        <Editor
            content={content}
            filename={currentFile}
            loading={loading}
            onChange={(value) => setContent(value || '')}
        />
    );
};
```

### Modal Integration

```tsx
import Modal from '@/components/ui/modal';

const EditorModal = ({ open, onClose, file }) => (
    <Modal open={open} onClose={onClose}>
        <Modal.Title>{file.name}</Modal.Title>
        <Editor
            content={file.content}
            filename={file.name}
            height="60vh"
            readOnly={!file.writable}
        />
    </Modal>
);
```

## Performance Considerations

- **Lazy Loading**: Monaco Editor is loaded only when the component mounts
- **Content Updates**: The editor efficiently handles content updates without full re-renders
- **Memory Management**: Proper cleanup when component unmounts

## Accessibility

The Editor component provides full accessibility support:

- **Keyboard Navigation**: All Monaco Editor keyboard shortcuts work
- **Screen Reader**: Compatible with screen readers
- **Focus Management**: Proper focus handling for modal usage
- **High Contrast**: Supports high contrast themes

## Browser Support

- **Modern Browsers**: Chrome, Firefox, Safari, Edge (latest versions)
- **Monaco Compatibility**: Follows Monaco Editor browser requirements
- **Mobile**: Limited support on mobile devices (Monaco Editor limitation)

## Migration from FileEditor

If you're migrating from the old FileEditor component:

```tsx
// Old FileEditor usage
<FileEditor
    open={isOpen}
    fileName="example.js"
    fileContent={content}
    loading={loading}
    readOnly={false}
    toggle={setIsOpen}
/>

// New approach with generic Editor
<Modal open={isOpen} onClose={() => setIsOpen(false)}>
    <Modal.Title>example.js</Modal.Title>
    <Editor
        content={content}
        filename="example.js"
        loading={loading}
        readOnly={false}
        onChange={handleContentChange}
        height="calc(100vh - 200px)"
    />
</Modal>
```

## Contributing

When extending the Editor component:

1. **Maintain Backward Compatibility**: Don't break existing props
2. **Add Type Definitions**: Update `editor.types.ts` for new props
3. **Update Examples**: Add examples for new features
4. **Test Thoroughly**: Test with different languages and scenarios
5. **Document Changes**: Update this documentation

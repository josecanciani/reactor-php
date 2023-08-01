
# Overview

Reactor allows your code to automatically update when changes to the component state is detected.

## Server and Client state

Every component has optional server variables (those declared in your server side component extending from the `Component` class), and optional client variables (defined in the `<script>` code of the component).

### About client variables

Client variables are parsed from the code, so it's important to keep a good indentation as it just search for `var|let|const` definitions in the first indentation it detects.

So from this code...

```javascript
<script>
    let myVar1 = 10;
      let myVar2 =20;
    const myVar3 = "hola";
  var myVar4;
</script>
```

... only `myVar1` and `myVar3` will be matched.

### Variable scope

If you want to define more variables (both in the server Component or the client JS block), and you don't want those variables to be used outside the component

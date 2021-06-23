# Deptrac

![](https://github.com/qossmic/deptrac/workflows/Continuous%20Integration/badge.svg?branch=main)

## What is Deptrac

Deptrac is a static code analysis tool that helps to enforce rules for dependencies between software layers in your PHP projects.

For example, you can define a rule like "controllers may not depend on models".
To ensure this, deptrac analyzes your code to find any usages of models in your controllers and will show you where
this rule has been violated.

![ModelController1](examples/ControllerServiceRepository1.png)

## Table of Contents

1. [Getting Started](#getting-started)
    1. [The Depfile](#the-depfile)
    1. [Explanation](#explanation)
1. [Installation](#installation)
    1. [PHAR](#phar)
    1. [Composer](#composer)
    1. [PHIVE](#phive)
    1. [Optional Dependency: Graphviz](#optional-dependency-graphviz)
1. [Run Deptrac](#run-deptrac)
    1. [Debug Layer](#debug-layer)
    1. [Debug Class-Like](#debug-class-like)
    1. [Debug Unassigned Classes](#debug-unassigned-classes)
3. [Layers](#layers)
    1. [Collecting Layers](#collecting-layers)
4. [Violations](#violations)
5. [Ruleset (Allowing Dependencies)](#ruleset-allowing-dependencies)
6. [Different Layers and Different Views](#different-layers-and-different-views)
7. [Collectors](#collectors)
    1. [`className` Collector](#classname-collector)
    1. [`classNameRegex` Collector](#classnameregex-collector)
    1. [`directory` Collector](#directory-collector)
    1. [`bool` Collector](#bool-collector)
    1. [`method` Collector](#method-collector)
    1. [`implements` Collector](#implements-collector)
    1. [`extends` Collector](#extends-collector)
    1. [`uses` Collector](#uses-collector)
    1. [`inherits` Collector](#inherits-collector)
    1. [Custom Collectors](#custom-collectors)
    1. [Ignoring `use` statements for namespaces](#ignoring-use-statements-for-namespaces)
8. [Formatters](#formatters)
    1. [Console Formatter](#console-formatter)
    1. [Table Formatter](#table-formatter)
    1. [Graphviz Formatter](#graphviz-formatter)
    1. [JUnit Formatter](#junit-formatter)
    1. [GitHubActions Formatter](#githubactions-formatter)
    1. [Baseline Formatter](#baseline-formatter)
    1. [Json Formatter](#json-formatter)
9. [Uncovered dependencies](#uncovered-dependencies)
10. [Import depfiles](#import-depfiles)
11. [Parameters](#parameters)
12. [Build Deptrac](#build-deptrac)
13. [Contribute](#contribute)

## Getting Started

The easiest way to get started is to download the latest [deptrac.phar](https://github.com/qossmic/deptrac/releases).

At first, you need a so called *depfile*, which is written in YAML.
You can generate a bootstrapped `depfile.yaml` with:

```bash
php deptrac.phar init
```

In this file you define (mainly) three things:

1. The location of your source code.
2. The layers of your application.
3. The allowed dependencies between your layers.


### The Depfile

Let's have a look at the generated file:

```yaml
# depfile.yaml
paths:
  - ./src
exclude_files:
  - '#.*test.*#'
layers:
  - name: Controller
    collectors:
      - type: className
        regex: .*Controller.*
  - name: Repository
    collectors:
      - type: className
        regex: .*Repository.*
  - name: Service
    collectors:
      - type: className
        regex: .*Service.*
ruleset:
  Controller:
    - Service
  Service:
    - Repository
  Repository: ~
```


#### Explanation

In the first section, `paths`, you declare where deptrac should look for your code.
As this is an array of directories, you can specify multiple locations.
Paths are relative to your depfile. It can be changed to the current working directory with setting following
option `use_relative_path_from_depfile: false`.

With the `exclude_files` section, you can specify one or more regular expressions for files that should be excluded,
the most common being probably anything containing the "test" word in the path.

We defined three `layers` in the example: *Controller*, *Repository* and *Service*.
Deptrac is using so called `collectors` to group classes into `layers`. You can define it by the name of the class or by the FQCN.

The `ruleset` section defines, how these layers may or may not depend on other layers.
In the example, every class of the *Controller* layer may depend on classes that reside in the *Service* layer,
and classes in the *Service* layer may depend on classes in the *Repository* layer.

Classes in the *Repository* layer may NOT depend on any classes in other layers.
The `ruleset` acts as a whitelist, therefore the *Repository* layer rules can be omitted, however
explicitly stating that the layer may not depend on other layers is more declarative.

If a class in the *Repository* layer uses a class in the *Service* layer, deptrac will recognize the dependency
and raises a violation for this case. The same counts if a *Service* layer class uses a *Controller* layer class.


## Installation

### PHAR

Download the latest [deptrac.phar](https://github.com/qossmic/deptrac/releases).
 
Run it using `php deptrac.phar` or feel free to add it to your PATH (i.e. `/usr/local/bin/deptrac`)

```bash
curl -LS https://github.com/qossmic/deptrac/releases/download/0.14.0/deptrac.phar -o deptrac.phar

# optional
sudo chmod +x deptrac.phar
sudo mv deptrac.phar /usr/local/bin/deptrac
```

(In this guide, we assume, you have the [deptrac.phar](https://github.com/qossmic/deptrac/releases) in your project root)

### Composer

We do not recommend installing this repository via Composer. Instead please use the dedicated distribution repository https://github.com/qossmic/deptrac-shim.

### PHIVE

You can install Deptrac with [Phive](https://phar.io/#Install)

`phive install -g qossmic/deptrac`

and accept the key with fingerprint `ED42 E915 4E81 A416 E7FB  A19F 4F2A B4D1 1A9A 65F7`

To upgrade Deptrac use the following command:

`phive update -g qossmic/deptrac`

### Optional Dependency: Graphviz

If you want to create graphical diagrams with your class dependencies, you will also need the `dot` command provided by [Graphviz](http://www.graphviz.org/).
Graphviz can be installed using common package managers:

```bash
# for osx + brew
brew install graphviz

# for ubuntu and debian
sudo apt-get install graphviz
```

Graphviz is also available for [Windows](https://graphviz.gitlab.io/_pages/Download/Download_windows.html): Install the current stable release and append the binary path on the environment variable Path (like ``C:\Program Files (x86)\Graphviz2.38\bin``).


## Run Deptrac

To execute deptrac, run

```bash
php deptrac.phar

# which is equivalent to
php deptrac.phar analyze depfile.yaml
```

If you run `php deptrac.phar -v` you'll get a more verbose output.

The analyze command runs with a caching mechanism for parsed files by default. This could be disabled with the `--no-cache` option.

### Debug Layer

With the `debug:layer`-command you can list all class-likes wich are matched in a specific layer.

```bash
php deptrac.phar debug:layer examples/DirectoryLayer.depfile.yaml Layer1

examples\Layer1\AnotherClassLikeAController
examples\Layer1\SomeClass
examples\Layer1\SomeClass2
```

### Debug Class-Like

With the `debug:class`-command you list all layers for a specific class-like.

```bash
php deptrac.phar debug:class-like examples/DirectoryLayer.depfile.yaml 'examples\Layer1\AnotherClassLikeAController'

Controller
Layer1
```

### Debug unassigned classes

With the `debug:unassigned`-command you list all classes in your path that are not assigned to any layer. This is useful to test that your collector configuration for layers is correct. 

```bash
php deptrac.phar debug:unassigned examples/DirectoryLayer.depfile.yaml 

examples\Layer1\AnotherClassLikeAController
examples\Layer1\SomeClass
examples\Layer1\SomeClass2
```

## Layers

Deptrac allows you to group different classes into *layers*.
Technically layers are nothing more than a collection of classes.

Each layer has a unique name and a list of one or more collectors, which will look for classes that should be assigned to this layer (and yes, classes can be assigned to more than one layer).

(Hopefully) most software is written with some kind of layers in mind.
For example a typical MVC application has at least controllers, models and views.

Deptrac allows you to visualize and enforce rulesets, based on such layer information.

So you could define that every class that ends with `Controller` will be assigned to the *Controller* layer, and
every class that has `\Model\` in its namespace will be added to the *Model* layer.

Say you are adopting MVC, most of the time you do not want your models to access controllers, but it is allowed for controllers
to access models. Deptrac allows you to enforce and visualize these dependencies/rules.

**By default, all dependencies between layers are forbidden!**


### Collecting Layers

If your application has *controllers* and *models*, deptrac allows you to
group them into layers.

```yaml
paths:
  - ./examples/ModelController
exclude_files: ~
layers:
  - name: Models
    collectors:
      - type: className
        regex: .*MyNamespace\\Models\\.*
  - name: Controller
    collectors:
      - type: className
        regex: .*MyNamespace\\.*Controller.*
ruleset: []
```

At first, lets take a closer look at the first layer (named *Models*).

Here we decided that our software has some kind of layer called *Models*.
You assign classes to this layer with the help of [*Collectors*](#collectors).

Collectors are responsible for taking a closer look at your code and decide if a class is part of a layer.
By using the `className` collector you can define a regular expression for a class name.
Every (fully qualified) class name that matches this regular expression becomes part of the assigned layer.
In this example we define that every class that contains `MyNamespace\Models\` will be a part of the *Model* layer.

Every class that matches `.*MyNamespace\\.*Controller.*` will become a part of the *Controller* layer.

As we defined our layers, we can generate a dependency graph for the example configuration:
(Make sure that [*Graphviz*](#optional-dependency-graphviz) (dot) is installed on your system)

```bash
php deptrac.php analyze examples/ModelController1.depfile.yaml
```

After deptrac has finished, an image should be opened:

![ModelController1](examples/ModelController1.png)

On your command line deptrac will produce this output:

```bash
Start to create an AstMap for 2 Files.
..
AstMap created.
start emitting dependencies "InheritanceDependencyEmitter"
start emitting dependencies "BasicDependencyEmitter"
end emitting dependencies
start flatten dependencies
end flatten dependencies
collecting violations.
formatting dependencies.

Found 0 Violations
```

The output shows, that deptrac is parsing 2 files and found 0 violations.
By default every dependency between layers is a violation.
In our case there are (for now) no dependencies between our classes (layers).
So it's fine that deptrac will show us 2 independent layers without any relationship.


## Violations

If we have 2 layers (*Models*, *Controller*) and one layer is using the other, deptrac will raise a violation by default:

```php
// see the example in examples/ModelController2
namespace examples\MyNamespace\Controllers;

use examples\MyNamespace\Models\SomeModel;

class SomeController
{
    public function foo(SomeModel $m) {
        return $m;
    }
}
```

After running deptrac for this example

```bash
php deptrac.php analyze examples/ModelController2.depfile.yaml
```

we will get this output:

```bash
Start to create an AstMap for 2 Files.
..
AstMap created.
start emitting dependencies "InheritanceDependencyEmitter"
start emitting dependencies "BasicDependencyEmitter"
end emitting dependencies
start flatten dependencies
end flatten dependencies
collecting violations.
formatting dependencies.
examples\MyNamespace\Controllers\SomeController::5 must not depend on examples\MyNamespace\Models\SomeModel (Controller on Models)
examples\MyNamespace\Controllers\SomeController::9 must not depend on examples\MyNamespace\Models\SomeModel (Controller on Models)

Found 2 Violations
```

![ModelController1](examples/ModelController2.png)

Deptrac has found two violations because the relation from the controller to model layers is not allowed.
The console output shows exactly the lines deptrac found.

### Skip violations

Deptrac integration into existing CI/CD pipeline might be difficult because of existing dependency violations in the code.
In this case, you can skip existing violations to gradually improve your code and avoid possibility introduce any new violations.

Violations can be skipped by provided list of dependencies in *skip_violations* configuration section:
```yaml
skip_violations:
  Library\LibClass:
    - Core\CoreClass
``` 
*skip_violations* section contains an associative array where a key (`Library\LibClass`) is the name of dependant class 
and values (`Core\CoreClass`) are dependency classes.

Matched violations will be marked as skipped:
```bash
php deptrac.php analyze examples/SkipViolations.yaml --report-skipped
1/1 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%

[SKIPPED] Library\LibClass must not depend on Core\CoreClass (Library on Core)
/path/examples/SkipViolations/SkipViolations.php::11

[ERROR] Skipped violation "Core\Unmatched" for "Library\LibClass" was not matched.

Report:
Violations: 0
Skipped violations: 1
Uncovered: 0
Allowed: 1
```

## Ruleset (Allowing Dependencies)

Allowed dependencies between layers are configured in *rulesets*.

By default deptrac will raise a violation for every dependency between layers.
In real software you want to allow dependencies between different kinds of layers.

As a lot of architectures define some kind of *controllers*, *services* and *repositories*, a natural approach for this would be to define these rules:

- *Controllers* may access *services*, but not *repositories*.
- *Services* may access *repositories*, but not *controllers*.
- *Repositories* neither may access services nor *controllers*.

We can define this using the following depfile:

```yaml
paths:
  - ./examples/ControllerServiceRepository1/
exclude_files: ~
layers:
  - name: Controller
    collectors:
      - type: className
        regex: .*MyNamespace\\.*Controller.*
  - name: Repository
    collectors:
      - type: className
        regex: .*MyNamespace\\.*Repository.*
  - name: Service
    collectors:
      - type: className
        regex: .*MyNamespace\\.*Service.*
ruleset:
  Controller:
    - Service
  Service:
    - Repository
  Repository: ~
```

Take a closer look at the ruleset.
We whitelist that *Controller* can access *Service* and *Service* can access *Repository*.

After running deptrac we will get this result:

![ModelController1](examples/ControllerServiceRepository1.png)

```bash
Start to create an AstMap for 3 Files.
...
AstMap created.
start emitting dependencies "InheritanceDependencyEmitter"
start emitting dependencies "BasicDependencyEmitter"
end emitting dependencies
start flatten dependencies
end flatten dependencies
collecting violations.
formatting dependencies.
examples\MyNamespace\Repository\SomeRepository::5 must not depend on examples\MyNamespace\Controllers\SomeController (Repository on Controller)
```

Deptrac now finds a violation.
If we take a closer look at the "SomeRepository" on line 5, we will see an unused use statement for a controller:

```php
namespace examples\MyNamespace\Repository;

use examples\MyNamespace\Controllers\SomeController;

class SomeRepository { }
```

If we remove the `use` statement and rerun deptrac, the violation will disappear.


### Transitive/inherited dependencies
Sometimes you might want to allow layer to depend on all layers that another layer depends on. For example if `layer A` depends on `layer B` and `layer B` depends on `layer C`, you also want implicitly for `layer A` to depend on `layer C` (hence transitive). Another way to put it, you want `layer A` to inherit all the dependencies of `layer B`. You can do that in deptrac with the use of `+` modifier: 
```yaml
layers:
  - name: layerA
  - name: layerB
  - name: layerC
ruleset:
  layerB:
    - layerC
  layerA:
    - +layerB # means `layerB` and `layerC`
```


## Different Layers and Different Views

In the example above we defined 3 different layers (*controller*, *repository* and *service*).
Deptrac gives architects the power to define what kind of layers exist.

Typically usecases are:

- caring about layers in different architectures (tier, hexagonal, ddd, ...)
- caring about dependencies between different kinds of services (infrastructure services / domain services / entities / DTOs / ...)
- caring about coupling to third party code like composer vendors, frameworks, ...
- enforcing naming conventions
- ...

Typically software has more than just one view.
**It is possible to use multiple depfiles, to take care about different architectural views.**


## Collectors

Collectors decide if a node (typically a class) is part of a layer.
Deptrac will support more collectors out of the box and will provide an
easy way to extend deptrac with custom collectors.

Technically, deptrac creates an [AST](https://en.wikipedia.org/wiki/Abstract_syntax_tree) from your code and groups nodes to different layers.


### `className` Collector

The `className` collector allows collecting classes by matching their fully qualified name to a simplified regular expression.
Any matching class will be added to the assigned layer.

```yaml
layers:
  - name: Controller
    collectors:
      - type: className
        regex: .*Controller.*
```

Every class name that matches the regular expression becomes a part of the *controller* layer.
This collector has predefined delimiters and modifier: `/YOUR_EXPRESSION/i`


### `classNameRegex` Collector

The `classNameRegex` collector allows collecting classes by matching their fully qualified name to a regular expression.
Any matching class will be added to the assigned layer.

```yaml
layers:
  - name: Controller
    collectors:
      - type: classNameRegex
        regex: '#.*Controller.*#'
```

Every class name that matches the regular expression becomes a part of the *controller* layer.


### `directory` Collector

The `directory` collector allows collecting classes by matching their file path they are declared in to a simplified regular expression.
Any matching class will be added to the assigned layer.

```yaml
layers:
  - name: Controller
    collectors:
      - type: directory
        regex: src/Controller/.*
```

Every file path that matches the regular expression `src/Controller/.*` becomes a part of the *controller* layer.
This collector has predefined delimiters and modifier: `#YOUR_EXPRESSION#i`


### `bool` Collector

The `bool` collector allows combining other collectors with or without negation.

```yml
layers:
  - name: Asset
    collectors:
      - type: bool
        must:
          - type: className
            regex: .*Foo\\.*
          - type: className
            regex: .*\\Asset.*
        must_not:
          - type: className
            regex: .*Assetic.*
```

Every class contains `Foo\` AND `\Asset` and NOT `Assetic`, will become a part of the *Asset* layer.


### `method` Collector

The `method` collector allows collecting classes by matching their methods name to a regular expression.
Any matching class will be added to the assigned layer.

```yaml
layers:
  - name: Foo services
    collectors:
      - type: method
        name: .*foo
```

Every class having a method that matches the regular expression `.*foo`, e.g. `getFoo()` or `setFoo()` becomes a part
of the *Foo services* layer.

### `implements` Collector

The `implements` collector allows collecting classes implementing a specified interface by matching recursively for a fully qualified interface name.

```yaml
layers:
  - name: Foo
    collectors:
      - type: implements
        implements: 'App\SomeInterface'
```

### `extends` Collector

The `extends` collector allows collecting classes extending a specified class by matching recursively for a fully qualified class or interface name.

```yaml
layers:
  - name: Foo
    collectors:
      - type: extends
        extends: 'App\SomeClass'
```

### `uses` Collector

The `uses` collector allows collecting classes using a specified trait by matching recursively for a fully qualified trait name.

```yaml
layers:
  - name: Foo
    collectors:
      - type: uses
        uses: 'App\SomeTrait'
```

### `inherits` Collector

The `inherits` collector allows collecting classes inheriting from a specified class, whether by implementing an interface, extending another class or by using a trait, by matching recursively for a fully qualified class name.

```yaml
layers:
  - name: Foo
    collectors:
      - type: inherits
        inherits: 'App\SomeInterface'
```

### Custom Collectors

You can even create custom collectors in your project by implementing the [`Qossmic\Deptrac\Collector\CollectorInterface`](https://github.com/qossmic/deptrac/blob/main/src/Collector/CollectorInterface.php).
As soon as an unknown collector is referenced in the config file deptrac will try to load the class in your project.
With this you can create collectors specific for your usecase. And more people can use these custom collectors per default if you contribute them back to deptrac!

### Ignoring `use` statements for namespaces

By default, deptrac will analyze all occurrences for classes, including `use` statements. If you would like to exempt `use` statements from the analysis, you can change this behaviour in the depfile:

```yaml
analyzer:
   count_use_statements: false
```
*Note:* This only applies in the context of including namespaces, `use` statements that are applying traits on classes are always counted. 

## Formatters

Deptrac has support for different output formatters with various options.

You can get a list of available formatters by running,

```bash
php deptrac.php analyze --help
```

*Hint*: Symfony Console does not allow to pass options to the default command. Therefore in order to use the formatter options you have to explicitly use the `analyze` command as shown above.

### Console Formatter

The default formatter is the console formatter, which dumps basic information to *STDOUT*,

```
examples\MyNamespace\Repository\SomeRepository::5 must not depend on examples\MyNamespace\Controllers\SomeController (Repository on Controller)
```

### Table Formatter

The table formatter groups results by layers to its own table. It can be activated with `--formatter=table`.

### Graphviz Formatter

The Graphviz formatter is disabled by default. It can be activated with `--formatter=graphviz`.
Deptrac automatically tries to open the image generated by Graphviz.
You can disable automatic opening of the image by setting the `--graphviz-display=false` option, which is useful on CI-servers.

#### Supported CLI options:

```
--graphviz-display=           should try to open graphviz image [default: true]
--graphviz-dump-image=        path to a dumped png file [default: ""]
--graphviz-dump-dot=          path to a dumped dot file [default: ""]
--graphviz-dump-html=         path to a dumped html file [default: ""]
```

*Hint*: You can create an image, a dot and an HTML file at the same time.

#### Supported depfile options:
Under `formatters.graphviz.hidden_layers` you can define a list of `layers` you do not want to include when using the corresponding `graphviz` output formatter. The generated image will not contain these layers, but they will be part of the analysis.

There are 2 main use-cases for this feature:
 - Hiding a generic/general domains like the `vendor` folder
- Having multiple "views" for your architecture. You can define a shared file with all your `layers` and a `ruleset` and then have multiple config files for the different `hidden_layers`. Using the `graphviz` formatter with these files will then generate graphs focusing on only the relevant layers.

```yaml
layers:
   - name: Utils
     collectors:
        - type: className
          regex: .*Util.*
   - name: Controller
     collectors:
        - type: className
          regex: .*Controller.*
ruleset:
   Controller:
      - Utils
formatters:
   graphviz:
      hidden_layers:
         - Utils
```

Another supported option is `formatters.graphviz.groups`. There you can sort layers into groups that will be rendered as sub-graphs in GraphViz output.

The following config:
```yaml
layers:
   - User Frontend
   - User Backend
   - Admin Frontend
   - Admin Backend
formatters:
   graphviz:
      groups:
         User:
            - User Frontend
            - User Backend
         Admin:
            - Admin Frontend
            - Admin Backend
```

Will produce the following graph:
![GraphvizGroups](examples/GraphvizGroups.png)

### JUnit Formatter

The JUnit formatter dumps a JUnit Report XML file, which is quite handy in CI environments.
It is disabled by default, to activate the formatter just use `--formatter=junit`.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<testsuites>
  <testsuite id="1" package="" name="Controller" timestamp="2018-06-07T10:09:34+00:00" hostname="localhost" tests="3" failures="2" errors="0" time="0">
    <testcase name="Controller-examples\Layer1\AnotherClassLikeAController" classname="examples\Layer1\AnotherClassLikeAController" time="0">
      <failure message="examples\Layer1\AnotherClassLikeAController:5 must not depend on examples\Layer2\SomeOtherClass (Controller on Layer2)" type="WARNING"/>
      <failure message="examples\Layer1\AnotherClassLikeAController:23 must not depend on examples\Layer2\SomeOtherClass (Controller on Layer2)" type="WARNING"/>
    </testcase>
  </testsuite>
  <testsuite id="2" package="" name="Layer2" timestamp="2018-06-07T10:09:34+00:00" hostname="localhost" tests="3" failures="4" errors="0" time="0">
    <testcase name="Layer2-examples\Layer2\SomeOtherClass2" classname="examples\Layer2\SomeOtherClass2" time="0">
      <failure message="examples\Layer2\SomeOtherClass2:5 must not depend on examples\Layer1\SomeClass2 (Layer2 on Layer1)" type="WARNING"/>
      <failure message="examples\Layer2\SomeOtherClass2:17 must not depend on examples\Layer1\SomeClass2 (Layer2 on Layer1)" type="WARNING"/>
    </testcase>
    <testcase name="Layer2-examples\Layer2\SomeOtherClass" classname="examples\Layer2\SomeOtherClass" time="0">
      <failure message="examples\Layer2\SomeOtherClass:5 must not depend on examples\Layer1\SomeClass (Layer2 on Layer1)" type="WARNING"/>
      <failure message="examples\Layer2\SomeOtherClass:17 must not depend on examples\Layer1\SomeClass (Layer2 on Layer1)" type="WARNING"/>
    </testcase>
  </testsuite>
</testsuites>
```

Supported options:

```
--junit-dump-xml=     path to a dumped xml file [default: "./junit-report.xml"]
```

### GitHubActions Formatter

The GithubActions formatter is a console formater, which dumps basic information in github-actions format to *STDOUT*.
This formatter is enabled by default while running in a github actions environment.
It can be activated manually with `--formatter=github-actions`.

```
::error file=/home/testuser/originalA.php,line=12::ACME\OriginalA must not depend on ACME\OriginalB (LayerA on LayerB)
```

### Baseline Formatter

The Baseline formatter is a console formater, which generates the `skip_violations` section to the given File.
With this formatter it's possible to start on a project with some violations without a failing CI Build.

*Note*: It's not the best solution to ignore all the errors because maybe your current Architecture doesn't allow a change without a new violation.

It can be activated with `--formatter=baseline`.

Supported options:

```
--baseline-dump[=BASELINE-DUMP] path to a dumped baseline file [default: "./depfile.baseline.yml"]
```

Include the baseline into your existing `depfile.yml`

```yaml
# depfile.yml
baseline: depfile.baseline.yml
``` 

### Json Formatter

By default, Json formatter dumps information to *STDOUT*. It can be activated with `--formatter=json`

```json
{
    "Report": {
        "Violations": 1,
        "Skipped violations": 2,
        "Uncovered": 1,
        "Allowed": 0,
        "Warnings": 0,
        "Errors": 0
    },
    "files": {
        "src/ClassA.php": {
            "violations": 2,
            "messages": [
                {
                    "message": "ClassA must not depend on ClassB (LayerA on LayerB)",
                    "line": 12,
                    "type": "error"
                },
                {
                    "message": "ClassA should not depend on ClassC (LayerA on LayerB)",
                    "line": 15,
                    "type": "warning"
                }
            ]
        },
        "src/ClassC.php": {
            "violations": 1,
            "messages": [
                {
                    "message": "ClassC should not depend on ClassD (LayerA on LayerB)",
                    "line": 10,
                    "type": "warning"
                }
            ]
        },
        "src/OriginalA.php": {
            "violations": 1,
            "messages": [
                {
                    "message": "OriginalA has uncovered dependency on OriginalB (LayerA)",
                    "line": 5,
                    "type": "warning"
                }
            ]
        }
    }
}
```

Supported options:

```
--json-dump= path to a dumped json file
```

## Uncovered dependencies

Deptrac collects uncovered dependencies which could be reported with [Console Formatter](#console-formatter).
By default, internal php classes will be ignored. This could be changed by adding `ignore_uncovered_internal_classes: false` to your depfile.

Use `--fail-on-uncovered` option to fail on uncovered dependencies.
Use `--report-uncovered` option to report uncovered dependencies.

## Import depfiles

It is possible to import other depfile definitions in your depfile as followed:

```yaml
imports:
   - some/depfile.yaml
```

## Parameters

Parameters can be used in a collector's configuration.  
Deptrac provides predefined parameters:
- `%currentWorkingDirectory%` The path deptrac has been executed
- `%depfileDirectory%` The path where the depfile is stored.

Custom parameters can be configured as followed:
```yaml
parameters:
    Project: MyProject

layers:
   - name: Foo
     collectors:
        - type: implements
          implements: '%Project%\SomeInterface'
```

## Build Deptrac

To build deptrac, clone this repository and ensure you have the build dependencies installed:

- PHP in version 7.2 or above
- [Composer](https://getcomposer.org/)
- [PHIVE](https://phar.io/)
- make

`cd` into your cloned directory, and call `make build`.

```bash
git clone https://github.com/qossmic/deptrac.git
cd deptrac
make build
```

This will create an executable file `deptrac.phar` in the current directory.
In order to use deptrac globally on your system, feel free to add it to your PATH (i.e. `/usr/local/bin`).


## Contribute

Deptrac is in a very early state, so it needs you to make it more awesome.

Feel free to report bugs, improve the documentation, request or even implement new features.

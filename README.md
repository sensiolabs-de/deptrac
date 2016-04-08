# Deptrac

## What is Deptrac

Deptrac is a static code analysis tool that helps to enforce rules for dependencies between software layers.

For example, you can define a rule like "controllers may not depend on models".
To ensure this, deptrac analyses your code to find any usages of models in your controllers and will show you where
this rule was violated.

![ModelController1](examples/ControllerServiceRepository1.png)


## Getting Started

The easiest way to get started is to download the depfile.phar.

At first, you need a so called *depfile*, which is written in YAML.
You can generate a bootstrapped `depfile.yml` with

```bash
php deptrac.phar init
```

In this file you define (mainly) three things:

1. The location of your sourcecode.
2. The layers of you application.
3. The allowed dependencies between your layers.


### The depfile

Let's have a look at the generated file:

```yaml
# depfile.yml 
paths:
  - ./src
exclude_files:
  - .*test.*
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
In the first section, `paths`, you declare, where deptrac should look for your code.
As this is an array of directories, you can specify multiple locations.

With the `exclude_files` section, you can specify one or more regular expression for files, that should be excludes,
the most common being probably anything containing the "test" word in the path.

In our example we defined three different layers *Controller*, *Repository* and *Service* in the `layers` section.
Deptrac is using so called `collectors` to group classes into `layers` (in this case by the name of the class).

The `ruleset` section defines, how these layers may or may not depend on other layers.
In the example every class of the *Controller*-layer may depend on classes that reside in the *Service*-layer,
and classes in the *Service*-layer may depend on classes in the *Repository*-layer.

Classes in the *Repository*-layer my NOT depend on any classes in other layers.
The `ruleset` acts as a whitelist, therefore the *Repository*-layer rules may be omitted, however
explicitly stating that the layer may not depend on other layers is more declarative.

If a class in the *Repository*-layer uses a class in the *Service*-layer, deptrac wil recognize the dependency and throw a violation for this case.
The same counts for if a *Service*-layer-class uses a *Controller*-layer-class.


## Installation


### Download the phar (recommended)
Download the [depfile.phar](https://get.sensiolans.de/deptrac.phar) and run it using `php deptrac.phar`.
Feel free to add it to your PATH (i.e. `/usr/local/bin/box`)

```bash
curl -LS https://get.sensiolans.de/deptrac.phar -o deptract.phar

# optional
sudo chmod +x deptrac.phar
sudo mv deptract.phar /usr/bin/local/deptrac
```

(In this guide, we assume, you have the `deptrac.phar` in your project root)


### Optional dependency: Graphviz

If you want to create graphical diagrams with your class dependencies, you will also need the `dot` command provided by [Graphviz](http://www.graphviz.org/).
There are packages for the usual package managers, for example:

```bash
# for osx + brew
brew install graphviz

# for ubuntu and debian
sudo apt-get install graphviz
```


## Run Deptrac

To execute deptrac, run

```bash
php deptrac.phar

# what es equivalent to
php deptrac.phar analyze depfile.yml
```


## Layers

Deptrac allows you to group different classes in *layers*.
Technically layers are nothing more than a collection of classes.

Each layer has a unique name and a list of one or more collectors, that will look for classes, that should be assigned to this layer
(and yes, classes can be assigned to more than one layer).

(Hopefully) most software is written with some kind of layers in mind.
For example a typically MVC application has at least controllers, models and views.

Deptrac allows you to visualize and enforce rulesets, based on such layer informations.

So, you could define, that every class, that ends with `Controller` will be assigned to the *Controller*-layer, and
every class, that has a `\Model\` in its namespace will be added to the *Model*-layer.

Saying, you're adopting MVC, most time you do not want your models to access controllers, but it is allowed for controllers
to access models. Deptrac allows you to enforce and visualize these dependencies / rules.

**By default, any dependencies between layers are forbidden!**


### Collecting Layers

If your application has *controllers* and *models*, deptrac allows you to
group them in layers.

```yaml
paths:
  - ./examples/ModelController
layers:
  - name: Models
    collectors:
      - type: className
        regex: .*MyNamespace\\Models\\.*
  - name: Controller
    collectors:
      - type: className
        regex: .*MyNamespace\\.*Controller.*
ruleset: ~
```

At first lets take a closer look at the first layer (named *Models*).

Here we decided that our software has some kind of layer called *Models*.
You assign classes to this layer with the help of *Collectors*.

Collectors are responsible for taking a closer look at your code and decide if a class is part of a layer.
By using the `className` collector you can define a regular expression for a class name.
Every (fully qualified) class name that matches this regular expression becomes part of the assigned layer.
In this example we define that every class that contains `MyNamespace\Models\` will be a part of the *Model* layer.

Every class that matches `.*MyNamespace\\.*Controller.*` will become a part of the *Controller* layer.

As we defined our layers, we can generate a dependency graph for the example configuration:
(Make sure that [*Graphviz*](#optional-dependency-graphviz) (dot) is installed on your system)

```bash
php deptrac.php analyze examples/ModelController1.depfile.yml
```

After deptrac has finished, an image should be opened:

![ModelController1](examples/ModelController1.png)

On your command line deptrac will produce this output:

```bash
Start to create an AstMap for 2 Files.
Parsing File SomeController.php
Parsing File SomeModel.php
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
By default every dependency between layers are violations.
In our case there are (for now) no dependencies between our classes (layers).
So it's fine that deptrac will show us 2 independent layers without any relationship.

## Violations
If we've 2 layers (*Models*, *Controller*) and one layer is using the other, deptrac will raise a violation by default:

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
php deptrac.php analyze examples/ModelController2.depfile.yml
```

we will get this output:

```bash
Start to create an AstMap for 2 Files.
Parsing File SomeController.php
Parsing File SomeModel.php
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

Deptrac has found two violations because the relation from the controller to models layer isn't allowed.
The console output shows exactly the lines deptrac found.


## Ruleset (allowing dependencies)

Allowed dependencies between layers are configured in *rulesets*.

By default deptrac will raise a violation for every dependency between layers.
In real software you want to allow dependencies between different kind of layers.

For example a lot of architectures define some kind of *Controllers*, *Services* and *Repositories*.
A natural approach would be allowing:

- controllers may access service, but not repositories
- services may access repositories, but not controllers
- repositories neither may access services nor controllers.

We can define this using such a depfile:

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

Take a closer look to the rulset, here we whitelist that controller can access service and service can access repository.

After running deptrac we'll get this result:

```
Start to create an AstMap for 3 Files.
Parsing File SomeController.php
Parsing File SomeRepository.php
Parsing File SomeService.php
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

![ModelController1](examples/ControllerServiceRepository1.png)

Deptrac now finds a violation, if we take a closer look at the "SomeRepository" on line 5,
we'll see an unused use statement to a controller:

```php
namespace examples\MyNamespace\Repository;

use examples\MyNamespace\Controllers\SomeController;

class SomeRepository { }
```

Now we can remove the use statement and rerun deptrac - now without any violation.

## Different Layers And Different Views
In the example above we defined 3 different layers (controller, repository and service).
Deptrac gives architects the power to define what kind of layers exists.

Typically usecases are:

- caring about layers in different architectures (tier, hexagonal, ddd, ...)
- caring about dependencies between different kinds of services (infrastructure services / domain services / entities / dto's / ...)
- caring about coupling to third party code like composer vendors, frameworks, ...
- enforcing naming conventions
- ...

Typically software has more than just one view,
it's totally fine to use multiple depfiles, to take care about different architectural views.


## Collectors

Deptrac groups nodes in your code's AST to different layers.
Collectors decide if a node (typically a class) is part of a layer.
deptrac will support more collectors out of the box and will provide an
easy way to extend deptrac with custom collectors.


### `className` Collector

Most examples are using the `className` collector.
The `className` collector allows collecting classes by matching their fully qualified name to a regular expression.
Any matching class will be added to the assigned layer.

```yaml
layers:
  - name: Controller
    collectors:
      - type: className
        regex: .*Controller.*

```


### `bool` Collector

The `bool` collector allows combining other collectors with or without negation.

```yml
layers:
  - name: Asset
    collectors:
      - type: bool
        must:
          - type: className
            regex: .*Foo\\Asset.*
          - type: className
            regex: .*Bar\\Asset.*
        must_not:
          - type: className
            regex: .*Assetic.*
```

Every class that contains `Foo\Asset` OR `Bar\Asset` and NOT `Assetic`, will become a part of the *Asset*-layer.


## Formatters

Deptrac has support for different output formatters with various options.

You can get a list of available formatters by running,

```bash
php deptrac.php analyze --help
```


### Console Formatter

The default formatter is the Console Formatter, which dumps basic informations to *STDOUT*,

```
examples\MyNamespace\Repository\SomeRepository::5 must not depend on examples\MyNamespace\Controllers\SomeController (Repository on Controller)
```

Supported Options:

```
--formatter-console=         to disable the console fomatter, set this option to 0 [default: 1]
```


### Graphviz Formatter

If Graphviz is installed, the Graphviz formatter will be activated by default.
After running deptrac with `--formatter-graphviz-display` enabled, deptrac tries to open the from Graphviz generated image.
For example on CI-Servers you can disable automatic opening of the image by setting the `--formatter-graphviz-display=0` option.

Supported Options:

```
--formatter-graphviz=                   to disable the graphviz fomatter, set this option to 0 [default: 1]
--formatter-graphviz-display=           should try to open graphviz image [default: true]
--formatter-graphviz-dump-image=        path to a dumped png file [default: ""]
--formatter-graphviz-dump-dot=          path to a dumped dot file [default: ""]
--formatter-graphviz-dump-html=         path to a dumped html file [default: ""]
```

You can create an image, a dot and a HTML file at the same time.


## Build deptrac

To build deptrac, clone this repository and ensure you have the build dependencies installed:

- PHP in version 5.5.9 or above
- [Composer](https://getcomposer.org/)
- [Box](http://box-project.github.io/box2/)
- make


```bash
git clone https://github.com/sensiolabs-de/deptrac.git 
cd deptrac 
composer install
make build
```

This will create an executable file `debtrac.phar` file in the current directory.
In order to use deptract globally on your system, feel free to add it to your PATH (i.e. `/usr/local/bin`)

关于架构设计
============

设计一个领域实体，必须明确这个领域实体必须知道其他哪些领域实体（帮助其完成业务），之后对于其他领域实体可以完全无视。

程序的启动程序的主要工作是：初始化view、model和controller。model比较封闭，初始化时一般不需要外部帮助；view初始化一般需要model数据等信息；controller需要view和model的引用，保证view和model信息的同步。

数据的流动方向和路径？

启动文件负责配置好所有组件,并在命名空间中保存引用.
所有view组件注册在mindMap.view下, 所有modal注册在mindMap.modal下, controller类似.
组件需要调用其它组件时, 可以将mindMap作为参数直接传递.


已经使用的设计模式
==================

1. Observer
2. Composition
3. Command
4. Template Method


**Composition模式**
类有两部分的功能: 管理子节点和递归操作

**Decorator模式**
https://gist.github.com/kampfer/5012413

**Abstract Factory/Factory Method**
抽象工厂模式依赖工厂方法模式 他们也经常和模板方法模式一起使用
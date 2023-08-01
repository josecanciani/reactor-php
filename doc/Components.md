# Overview

A Component is the basic building block of the UI in a Reactor application. It defines a DOM tree of elements and other nested Components that will be rendered to the client browser.

## Component types

There are two basic components:
* Client Component: a component that only has client variables, hence it's rendering does not depend on the server state.
  * This means that if a client already has the Component definition, it can create the component without any type of server activity or connection.
  * It may have other nested Server components, but this does not change how the widget behaves.
* Server Component: this type will have variables that when changed on the client, will produce a new fetch. They cannot be used without connection, altought a cached version can be used if requested and we have the data for it.

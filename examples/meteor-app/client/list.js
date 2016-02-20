Template.list.helpers({
    fooList : function() {
        return Foo.find();
    }
});

Template.list.events({
    'click li.node' : function(e) {
        var id = e.target.getAttribute('data-id');
        Foo.remove({_id : id});
    },
    'click #addButton' : function(e) {
        var value = document.getElementById('addInput').value;
        Foo.insert({value : value});
    }
});
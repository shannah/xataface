//require <xatacard/layout/RecordSet.js>
//require <xatacard/layout/Record.js>
//require <xatacard/layout/Schema.js>
//require <xatacard/layout/Field.js>
//require <tests/TestRunner.js>
//require <stacktrace.min.js>

(function(){
	var TestRunner,
		RecordSet,
		Record,
		Schema,
		Field,
		Exception,
		PropertyChangeListener,
		PropertyChangeEvent;
		
	XataJax.ready(function(){
		TestRunner = tests.TestRunner;
		RecordSet = xatacard.layout.RecordSet;
		Record = xatacard.layout.Record;
		Schema = xatacard.layout.Schema;
		Field = xatacard.layout.Field;
		PropertyChangeListener = XataJax.beans.PropertyChangeListener;
		PropertyChangeEvent = XataJax.beans.PropertyChangeEvent;
		Exception = XataJax.Exception;
	});
	
	function RecordSetTest(){
	
		XataJax.extend(this, new TestRunner());
		
		
		XataJax.publicAPI(this, {
			getTests: getTests
		});
		
		var self = this;
		
		
		function getTests(){
		
			return [
			
				testRecordSetGettersAndSetters,
				testRecordGettersAndSetters,
				testSchema,
				testField
			];
		}
		
		
		function testRecordSetGettersAndSetters(){
			
			
			var rs = new RecordSet();
			
			self.assertEquals('initial cardinality', null, rs.getCardinality());
			rs.setCardinality(2);
			self.assertEquals('cardinality after setting', 2, rs.getCardinality());
			
		}
		
		function testRecordGettersAndSetters(){
			
			var r = new Record();
			var s = new Schema();
			
			var schemaChangeEvent = null;
			
			r.addPropertyChangeListener(new PropertyChangeListener({
				propertyChange: function(evt){
					schemaChangeEvent = evt;
				}
			}));
			
			r.setSchema(s);
			
			self.assertTrue('record propertyChangeEvent(schema)', XataJax.instanceOf(schemaChangeEvent, PropertyChangeEvent));
			self.assertEquals('record propertyChangeEvent(schema) propertyName', schemaChangeEvent.propertyName, 'schema');
			self.assertEquals('record propertyChangeEvent(schema) oldValue', schemaChangeEvent.oldValue, null);
			self.assertEquals('record propertyChangeEvent(schema) newValue', schemaChangeEvent.newValue, s);
			self.assertEquals('record propertyChangeEvent(schema) source', schemaChangeEvent.source, r);
			
			var code = null;
			try {
				r.setValue('foo','bar');
			} catch (ex){
				code = ex.getCode();
			}
			self.assertEquals('record set value not in schema', code, XataJax.errorcodes.PATH_NOT_IN_SCHEMA);
			
			code = null;
			try {
				r.getValue('foo');
			} catch (ex){
				code = ex.getCode();
			}
			self.assertEquals('record get value not in schema', code, XataJax.errorcodes.PATH_NOT_IN_SCHEMA);
			
			
			var nameField = new Field({path:'name'});
			s.addField(nameField);
			self.assertTrue('record isDirty before changes', !r.isDirty());
			self.assertTrue('record valueChanged before changes', !r.valueChanged('name'));
			r.setValue('name','Steve');
			self.assertEquals('record set/get value in schema', r.getValue('name'), 'Steve');
			self.assertTrue('record isDirty after changes', r.isDirty());
			self.assertTrue('record valueChanged after changes', r.valueChanged('name'));
			
			var snapshot = r.getSnapshot();
			self.assertEquals('record snapshot name value', snapshot.name, null);
			
			// test out the triggers.
			
			var beforeSetValueEvent = null;
			var afterSetValueEvent = null;
			var afterSchemaSetValueEvent = null;
			var beforeSchemaSetValueEvent = null;
			
			r.bind('beforeSetValue', function(evt){
				//alert(evt.newValue);
				beforeSetValueEvent = evt;
			});
			
			r.bind('afterSetValue', function(evt){
				afterSetValueEvent = evt;
			});
			
			s.bind('afterSetValue', function(evt){
				afterSchemaSetValueEvent = evt;
			});
			
			s.bind('beforeSetValue', function(evt){
				beforeSchemaSetValueEvent = evt;
			});
			
			
			
			r.setValue('name', 'Steve'); // shouldn't fire a trigger - no value change
			self.assertEquals('record beforeSetValue event not fired', beforeSetValueEvent, null);
			
			
			
			r.setValue('name', 'Dave'); // should fire trigger
			//alert(beforeSetValueEvent.newValue);
			//alert(XataJax.instanceOf(beforeSetValueEvent, PropertyChangeEvent));
			//alert(PropertyChangeEvent);
			//alert(beforeSetValueEvent.constructor);
			self.assertTrue('record beforeSetValue event fired', XataJax.instanceOf(beforeSetValueEvent, PropertyChangeEvent));
			self.assertEquals('record beforeSetValue event property name', beforeSetValueEvent.propertyName, 'name');
			self.assertEquals('record beforeSetValue event poperty old value', beforeSetValueEvent.oldValue, 'Steve');
			self.assertEquals('record beforeSetValue event property new value', beforeSetValueEvent.newValue, 'Dave');
			self.assertEquals('record beforeSetValue event source', beforeSetValueEvent.source, r);
			self.assertEquals('record getValue after setting value', r.getValue('name'), 'Dave');
		
		
			self.assertTrue('record afterSetValue event fired', XataJax.instanceOf(afterSetValueEvent, PropertyChangeEvent));
			self.assertEquals('record afterSetValue event property name', afterSetValueEvent.propertyName, 'name');
			self.assertEquals('record afterSetValue event poperty old value', afterSetValueEvent.oldValue, 'Steve');
			self.assertEquals('record afterSetValue event property new value', afterSetValueEvent.newValue, 'Dave');
			self.assertEquals('record afterSetValue event source', afterSetValueEvent.source, r);
			
		
			self.assertTrue('record afterSetValue schema event fired', XataJax.instanceOf(afterSchemaSetValueEvent, PropertyChangeEvent));
			self.assertEquals('record afterSchemaSetValue event property name', afterSchemaSetValueEvent.propertyName, 'name');
			self.assertEquals('record afterSchemaSetValue event poperty old value', afterSchemaSetValueEvent.oldValue, 'Steve');
			self.assertEquals('record afterSchemaSetValue event property new value', afterSchemaSetValueEvent.newValue, 'Dave');
			self.assertEquals('record afterSchemaSetValue event source', afterSchemaSetValueEvent.source, r);
			
			self.assertTrue('record beforeSetValue schema event fired', XataJax.instanceOf(afterSchemaSetValueEvent, PropertyChangeEvent));
			self.assertEquals('record beforeSchemaSetValue event property name', beforeSchemaSetValueEvent.propertyName, 'name');
			self.assertEquals('record beforeSchemaSetValue event poperty old value', beforeSchemaSetValueEvent.oldValue, 'Steve');
			self.assertEquals('record beforeSchemaSetValue event property new value', beforeSchemaSetValueEvent.newValue, 'Dave');
			self.assertEquals('record beforeSchemaSetValue event source', beforeSchemaSetValueEvent.source, r);
			
			
			// Try introducing a separate schema.
			var s2 = new Schema();
			var nameField2 = new Field({path: 'name'});
			s2.addField(nameField2);
			
			r.setSchema(s2);
			
			self.assertTrue('record propertyChangeEvent(schema) second time', XataJax.instanceOf(schemaChangeEvent, PropertyChangeEvent));
			self.assertEquals('record propertyChangeEvent(schema) second time propertyName', schemaChangeEvent.propertyName, 'schema');
			self.assertEquals('record propertyChangeEvent(schema) second time oldValue', schemaChangeEvent.oldValue, s);
			self.assertEquals('record propertyChangeEvent(schema) second time newValue', schemaChangeEvent.newValue, s2);
			self.assertEquals('record propertyChangeEvent(schema) second time source', schemaChangeEvent.source, r);
			
			
			beforeSchemaSetValueEvent = null;
			r.setValue('name', 'Daniel');
			
			// We are checking here to make sure that the beforeSetValue event was properly
			// unbound from the old schema when the schema was changed.
			self.assertEquals('record beforeSetValueEvent in schema after changing to different schema', beforeSchemaSetValueEvent, null);			
			
			
			// Now let's do some datasource testing.
			var dsException = null;
			try {
				r.commit();
			} catch (ex){
				dsException = ex;
			}
			
			//alert(dsException);
			//alert(dsException.constructor);
			self.assertTrue('record committing without datasource exception', XataJax.instanceOf(dsException, Exception));
			self.assertEquals('record committing without datasource exception code', dsException.getCode(), XataJax.errorcodes.NOT_BOUND_TO_DATASOURCE);
			
			
		
		}
		
		
		function testSchema(){
			var s = new Schema();
			
			self.assertEquals('schema createRecord', s.createRecord().constructor, Record);
			
			var nameField = new Field({path:'name'});
			var addressField = new Field({path:'address'});
			var phoneField = new Field({path:'phone'});
			
			s.addField(nameField);
			self.assertEquals('schema getField("name")', s.getField('name'), nameField);
			
			nameField.setPath('name2');
			self.assertEquals('schema getField("name2")', s.getField('name2'), nameField);
			self.assertEquals('schema getField("name") after changing name', s.getField('name'), null);
			
			self.assertEquals('field property change listeners before removal', nameField.getPropertyChangeListeners().length, 1);
			
			s.removeField(nameField);
			self.assertEquals('schema getField("name2") after removal', s.getField('name2'), null);
			
			// Make sure that the listener has been removed.
			self.assertEquals('field property change listeners after removal', nameField.getPropertyChangeListeners().length, 0);
			
			
			
			
			
		}
		
		
		function testField(){
		
			var fld = new Field({
				path: 'path/to/me'
			});
			
			self.assertEquals('field getPath after constructor', fld.getPath(), 'path/to/me');
			fld.setPath('foo');
			self.assertEquals('field getPath after setPath', fld.getPath(), 'foo');
			
			
			// Now to test property change events
			var numPropertyChangeCalls = 0;
			var listener = new PropertyChangeListener({
				propertyChange: function(evt){
					var name = evt.propertyName;
					var source = evt.source;
					var old = evt.oldValue;
					var newVal = evt.newValue;
					self.assertEquals('field property change for path name', name, 'path');
					self.assertEquals('field property change old value bar', old, 'foo');
					self.assertEquals('field property change new value var', newVal, 'bar');
					self.assertEquals('field property change source', source, fld);
					numPropertyChangeCalls++;
				}
			});
			
			fld.addPropertyChangeListener(listener);
			fld.setPath('bar');
			self.assertEquals('field property change number of propertyChangeCalls', numPropertyChangeCalls, 1);
		}
	}
	
	XataJax.main(function(){
		$(document).ready(function(){
			var test = new RecordSetTest();
			test.runTests();
		});
	});
	
})();
//require <jquery.packed.js>

(function(){
    var $ = jQuery;
    var pkg = XataJax.load('xatafce.data');
    pkg.DataModel = DataModel;
    
    function DataModel(o){
        o = o || {};
        var self = this;
        
        var stateId = -1;
        
        var entityTypes = {};
        var dataMappers = {};
        
        var entities = {};
        var department = null;
        /**
         * Next ID to be used when creating a new local entity.  This is only
         * temporary as the entity's ID will be reassigned when adding to the
         * database.
         * @type Number
         */
        var nextId = -1;
        /**
         * Items to synchronize.  This maps entity names (e.g. courses, courseSections)
         * to an array of courses and course sections to be synchronized.
         * @type Array
         */
        var adds = {};
        var addsInProgress = {};
        /**
         * Array of items that have been removed since last synchronize.
         * @type Array
         */
        var removes = {};
        var removesInProgress = {};
        /**
         * 
         * @type type
         */
        var changes = {};
        var changesInProgress = {};
        /**
         * Map of collections that should be managed by this datamodel.  Managed
         * collections will have their keys updated when the IDs of entities change.
         * Collections should hold only one type of entity, and keys should be the
         * entity ID.
         * @type Object[String => Entity]
         */
        var managedCollections = {};
        /**
         *  A callback function used to add to all entities that listens for 
         *  change events.  It is up to the entities to fire these change events
         *  so that the data model will know when to push updates to the server.
         * @type Function
         */
        var changeCallback = function(evt, data){
            // Note:  This function is passed as an event handler
            // to an individual entity.  Therefore "this" refers
            // to the entity, not the DataModel object.
            var type = self.getEntityType(this);
            var id = self.getEntityId(this);
            if ( id < 0 ){
                // this entity is not persisted yet, we don't need to store
                // the changes
                return;
            }
            var changesForType = self.changes[type] || {};
            var existingChanges = changesForType[id] || {};
            $.extend(existingChanges, data);
            changesForType[id] = existingChanges;
            self.changes[type] = changesForType;

        };
        /**
         * Lock to indicate that a sync is in progress so that additional sync requests
         * won't be initiated until the sync is complete.
         * @type Boolean
         */
        var syncInProgress = false;
        Object.defineProperties(this, {
            nextId : {
                enumerable : true,
                configurable : false,
                get : function(){ return nextId;},
                set : function(val){ nextId = val;}
                
            },
            stateId : {
                enumerable : true,
                configurable : false,
                get : function(){
                    return stateId;
                },
                set : function(val){
                    stateId = val;
                }
                
            },
            department : {
                enumerable : true,
                configurable : false,
                get : function(){
                    return department;
                },
                set : function(val){
                    department = val;
                }
            },
            entities : {
                enumerable : false,
                configurable : false,
                get : function(){ return entities;}
                
            },
            adds : {
                enumerable : false,
                configurable : false,
                get : function(){
                    return adds;
                }
            },
            addsInProgress : {
                enumerable : false,
                configurable : false,
                get : function(){
                    return addsInProgress;
                }
            },
            removes : {
                enumerable : false,
                configurable : false,
                get : function(){
                    return removes;
                }
            },
            removesInProgress : {
                enumerable : false,
                configurable : false,
                get : function(){
                    return removesInProgress;
                }
            },
            changes : {
                enumerable : false,
                configurable : false,
                get : function(){
                    return changes;
                }
            },
            changesInProgress : {
                enumerable : false,
                configurable : false,
                get : function(){
                    return changesInProgress;
                }
            },
            changeCallback : {
                enumerable : false,
                configurable : false,
                get : function(){
                    return changeCallback;
                }
            },
            managedCollections : {
                enumerable : false,
                configurable : false,
                get : function(){
                    return managedCollections;
                }
            }
        });
        $.extend(this, o);
    }
    
    $.extend(DataModel.prototype, {
        getEntityType : function(/*Object*/ entity){
            if ( entity.entityType instanceof pkg.EntityType ){
                return entity.entityType;
            } else {
                var outType = null;
                for ( var entityTypeName in this.entityTypes ){
                    if ( entity instanceof this.entityTypes[entityTypeName].entityClass ){
                        return this.entityTypes[entityTypeName];
                    }
                }
            } 
            throw {code : 500, message : 'Unknown entity type '+entity};
        },
        getEntityId : function(/*Object*/ entity){
            var entityType = this.getEntityType(entity);
            return entity[entityType.idProperty];
            
        },
        setEntityId : function(/*Object*/ entity, /*Number*/ id){
            var entityType = this.getEntityType(entity);
            entity[entityType.idProperty] = parseInt(id);
            
        },
        updateEntityReferences : function(/*String*/ type, /*Number*/ oldid, /*Number*/ newid){
            if ( type instanceof pkg.EntityType ){
                type = type.name;
            }
            oldid = parseInt(oldid);
            newid = parseInt(newid);
            var entitiesForType = this.entities[type] || {};
            var entity = entitiesForType[oldid];
            if ( entity !== undefined ){
                delete entitiesForType[oldid];
                entitiesForType[newid] = entity;

                var addsForType = this.adds[type] || {};
                delete addsForType[oldid];
                addsForType[newid] = entity;
                
                var removesForType = this.removes[type] || {};
                delete removesForType[oldid];
                removesForType[newid] = entity;
                
                var changesForType = this.changes[type] || {};
                var changes = changesForType[oldid];
                if ( changes !== undefined ){
                    delete changesForType[oldid];
                    changesForType[newid] = changes;
                }
                
                // Now update all managed collections
                $.each(this.managedCollections, function(type, collections){
                    $.each(collections, function(k,collection){
                        if ( collection[oldid] !== undefined ){
                            var e = collection[oldid];
                            delete collection[oldid];
                            collection[newid] = e;
                        }
                    });
                });
            }
            
            
        },
        propertiesToColumns : function(/*String*/type, /*Object*/ data){
            if ( type instanceof pkg.EntityType ){
                type = type.name;
            }
            var mapper = this.dataMappers[type];
            if ( mapper && typeof(mapper.propertiesToColumns) === 'function' ){
                return mapper.propertiesToColumns(data);
            }
            return data;
                
        
        },
        columnsToProperties : function(/*String*/type, /*Object*/data){
            if ( type instanceof pkg.EntityType ){
                type = type.name;
            }
            var mapper = this.dataMappers[type];
            if ( mapper && typeof(mapper.columnsToProperties) === 'function' ){
                return mapper.columnsToProperties(data);
            }
            return data;
        },
        newEntity : function(type, data){
            if ( !type instanceof pkg.EntityType ){
                type = this.entityTypes[type];
            }
            var out = null;
            if ( typeof(type.newInstance) === 'function' ){
                out = type.newInstance(data);
            } else {
                out = new type.entityClass(data);
            }
            return out;
        },
        newCourseSection : function(data){
            var course = data.course || this.fetchCourseById(data.courseId);
            if ( !course ){
                throw {
                    code : 404,
                    message : 'Failed to load course with course ID '+data.courseId
                };
            }
            var section = new models.CourseSection({
                courseSectionId : data.courseSectionId || this.nextId--,
                course : course,
                semester : data.semester,
                sectionNumber : data.sectionNumber,
                actualEnrol : data.actualEnrol,
                plannedEnrol : data.plannedEnrol
            });
            
            return section;
        },
        newCourse : function(data){
            return new models.Course({
                courseId : data.courseId || this.nextId--,
                subject : data.subject,
                catalogNumber : data.catalogNumber,
                title : data.title
            });
        },
        newLoadPlan : function(data){
            var facultyMember = data.facultyMember || this.fetchFacultyMemberById(data.facultyId);
            var academicYear = data.academicYear || models.AcademicYear.createYear(data.startYearFall);
            if ( !facultyMember ){
                console.log("No faculty member found");
                console.log(data);
            }
            function buildCredits(credits){
                
            }
            
            return new models.LoadPlan({
                loadPlanId : data.loadPlanId || this.nextId--,
                facultyMember : facultyMember,
                academicYear : academicYear,
                courseGoals : data.courseGoals,
                gradSupervisorCreditsApplied : data.gradSupervisorCreditsApplied,
                gradSupervisorCreditsEarned : data.gradSupervisorCreditsEarned,
                notes : data.notes
  
            });
        },
        newFacultyMember : function(data){
            var o = $.extend({}, data);
            if ( !o.facultyMemberId ){
                o.facultyMemberId = this.nextId--;
            }
            return new models.FacultyMember(o);
        },
        newSemesterActivity : function(data){
            var o = {};
            $.extend(o, data);
            if ( !o.facultyMember && o.facultyMemberId ){
                o.facultyMember = this.fetchFacultyMemberById(o.facultyMemberId);
                if ( !o.facultyMember ){
                    console.log("Couldn't find faculty member");
                    console.log(o);
                }
            }
            if ( o.semester ){
                o.semester = models.Semester.parse(data.semester);
            }
            if ( !o.semesterActivityId ){
                o.semesterActivityId = this.nextId--;
            }
            return new models.SemesterActivity(o);
        },
        newRank : function(data){
            return new models.Rank(data);
        },
        newCourseInstructor : function(data){
            var courseSection = data.courseSection || this.fetchCourseSectionById(data.courseSectionId);
            if ( !courseSection){
                throw "Course section not found "+data.courseSectionId;
            }
            var facultyMember = data.facultyMember || this.fetchFacultyMemberById(data.instructorId);
            if ( !facultyMember ){
                console.log("Could not find faculty member: ");
                console.log(data);
            }
            var o = $.extend({}, data);
            o.courseSection=courseSection;
            o.facultyMember=facultyMember;
            delete o.courseSectionId;
            delete o.instructorId;
            if ( !o.courseInstructorId ){
                o.courseInstructorId = this.nextId--;
            }
            return new models.CourseInstructor(o);
        },
        newInstructorPreference : function(data){
            var course = data.course || this.fetchCourseById(data.courseId);
            if ( !course ){
                throw "Course not found "+data.courseId;
            }
            var facultyMember = data.facultyMember || this.fetchFacultyMemberById(data.facultyMemberId);
            if ( !facultyMember ){
                console.log("Could not find faculty member:");
                console.log(data);
            }
            var o = $.extend({}, data);
            o.course = course;
            o.facultyMember = facultyMember;
            delete o.courseId;
            delete o.facultyMemberId;
            if ( !o.instructorPreferenceId ){
                o.instructorPreferenceId = this.nextId--;
            }
            return new models.InstructorPreference(o);
        },
        fetchEntityById : function(/*String*/ type, /*Number*/id){
            id = parseInt(id);
            var entitiesOfType = this.entities[type] || {};
            //console.log(entitiesOfType);
            return entitiesOfType[id];
        },
        fetchCourseById : function(id){
            return this.fetchEntityById('Course', id);
        },
        fetchCourseSectionById : function(id){
            return this.fetchEntityById('CourseSection', id);
        },
        fetchLoadPlanById : function(id){
            return this.fetchEntityById('LoadPlan', id);
        },
        fetchFacultyMemberById : function(id){
            return this.fetchEntityById('FacultyMember', id);
        },
        fetchSemesterActivityById : function(id){
            return this.fetchEntityById('SemesterActivity', id);
        },
        fetchRankById : function(id){
            return this.fetchEntityById('Rank',id);
        },
        fetchCourseInstructorById : function(id){
            return this.fetchEntityById('CourseInstructor', id);
        },
        fetchInstructorPreferenceById : function(id){
            return this.fetchEntityById('InstructorPreference', id);
        },
        findAllEntitiesOfType : function(type){
            return this.entities[type];
        },
        findAllCourses : function(){
            return this.findAllEntitiesOfType('Course');
        },
        findAllCourseSections : function(){
            return this.findAllEntitiesOfType('CourseSection');
        },
        findAllLoadPlans : function(){
            return this.findAllEntitiesOfType('LoadPlan');
        },
        findAllFacultyMembers : function(){
            return this.findAllEntitiesOfType('FacultyMember');
        },
        findAllSemesterActivities : function(){
            return this.findAllEntitiesOfType('SemesterActivity');
        },
        findAllRanks : function(){
            return this.findAllEntitiesOfType('Rank');
        },
        findAllCourseInstructors : function(){
            return this.findAllEntitiesOfType('CourseInstructor');
        },
        findAllInstructorPreferences : function(){
            return this.findAllEntitiesOfType('InstructorPreference');
        },
        addEntity : function(/*Object*/ entity){
            var type = this.getEntityType(entity);
            var id = this.getEntityId(entity);
            var toAdd = false;
            if ( id < 0 ){
                toAdd = true;
            }
            var entitiesOfType = this.entities[type] || {};
            if ( entitiesOfType[id] !== undefined ){
                
                $(entitiesOfType[id]).unbind('change', this.changeCallback);
                
                
            }
            
            entitiesOfType[id] = entity;
            
            if ( entitiesOfType[id] !== undefined ){
                $(entity).bind('change', this.changeCallback);
            }
            
            this.entities[type] = entitiesOfType;
            
            if ( toAdd ){
                var added = this.adds[type] || {};
                added[id] = entity;
                this.adds[type] = added;
                
                
            }
            
            
            switch ( this.getEntityType(entity) ){
                case 'FacultyMember' :
                    if ( entity.lastName === 'TBA' ){
                        models.FacultyMember.TBA = entity;
                    }
                    this.startManagingCollection('CourseSection', entity.courseAssignments);
                    break;
                case 'SemesterActivity' :
                    entity.facultyMember.semesterActivities[entity.semester.code] = entity;
                    break;
                case 'LoadPlan' :
                    entity.facultyMember.loadPlans[entity.academicYear.startCalendarYear] = entity;
                    break;
                case 'CourseInstructor':
                    entity.courseSection.addInstructor(entity);
                    var lp = entity.facultyMember.getLoadPlan(entity.courseSection.semester.academicYear.startCalendarYear);
                    if ( lp ){
                        lp.addCourseTaught(entity);
                    }
                    entity.facultyMember.addCourseAssignment(entity);
                    
                    break;
                case 'InstructorPreference':
                    entity.facultyMember.addInstructorPreference(entity);
                    entity.course.addInstructorPreference(entity);
                    break;
            }
           
        },
        addCourse : function(/*Course*/ course){
            this.addEntity(course);
        },
        addCourseSection : function(/*CourseSection*/ courseSection){
            this.addEntity(courseSection);
        },
        addLoadPlan : function(/*LoadPlan*/ loadPlan){
            this.addEntity(loadPlan);
        },
        addFacultyMember : function(/*FacultyMember*/ facultyMember){
            this.addEntity(facultyMember);
        },
        addSemesterActivity : function(/*SemesterActivity*/ semesterActivity){
            this.addEntity(semesterActivity);
        },
        addCourseInstructor : function(/*CourseInstructor*/ courseInstructor){
            this.addEntity(courseInstructor);
        },
        removeEntityById : function(/*String*/ type, /*Number*/ id){
            var entitiesOfType = this.entities[type] || {};
            var entity = null;
            if ( entitiesOfType[id] !== undefined ){
                entity = entitiesOfType[id]; 
                $(entitiesOfType[id]).unbind('change', this.changeHandler);
                delete entitiesOfType[id];
                this.entities[type] = entitiesOfType;
                
            }
            
            var added = this.adds[type] || {};
            var removed = this.removes[type] || {};
            if ( added[id] !== undefined ){
                delete added[id];
                this.adds[type] = added;
            } else if ( entity ) {
                removed[id] = entity;
                this.removes[type] = removed;
            }
            
            var changed = this.changes[type] || {};
            if ( changed[id] !== undefined ){
                delete changed[id];
            }
        },
        removeEntity : function(/*Object*/entity){
            var type = this.getEntityType(entity);
            var id = this.getEntityId(entity);
            this.removeEntityById(type, id);
            switch (type){
                case 'FacultyMember':
                    this.stopManagingCollection('CourseSection', entity.courseAssignments);
                    break;
                case 'SemesterActivity':
                    var a = entity.facultyMember.semesterActivities[entity.semester.code];
                    if ( a === entity ){
                        delete entity.facultyMember.semesterActivities[entity.semester.code];
                    }
                    break;
                case 'LoadPlan':
                    var a = entity.facultyMember.loadPlans[entity.academicYear.startCalendarYear];
                    if ( a === entity ){
                        delete entity.facultyMember.loadPlans[entity.academicYear.startCalendarYear];
                    }
                    break;
                case 'CourseInstructor':
                    var a = entity.facultyMember.getCourseAssignmentForSection(entity.courseSection);
                    if ( a === entity ){
                        entity.facultyMember.removeCourseAssignment(entity);
                    }
                    entity.courseSection.removeInstructor(entity);
                    
                    
                    var lp = entity.facultyMember.getLoadPlan(entity.courseSection.semester.academicYear.startCalendarYear);
                    if ( lp ){
                        lp.removeCourseTaught(entity);
                    }
                    break;
                case 'InstructorPreference':
                    entity.facultyMember.removeInstructorPreference(entity);
                    entity.course.removeInstructorPreference(entity);
                    break;
            }
            
        },
        removeCourse : function(/*Course*/ course){
            this.removeEntity(course);
        },
        removeCourseSection : function(/*CourseSection*/ courseSection){
            this.removeEntity(courseSection);
        },
        removeLoadPlan : function(/*LoadPlan*/ loadPlan){
            this.removeEntity(loadPlan);
        },
        removeFacultyMember : function(/*FacultyMember*/ facultyMember){
            this.removeEntity(facultyMember);
        },
        removeSemesterActivity : function(/*SemesterActivity*/ semesterActivity){
            this.removeEntity(semesterActivity);
        },
        removeCourseInstructor : function(/*CourseInstructor*/ courseInstructor){
            this.removeEntity(courseInstructor);
        },
        buildCoursePlan : function(/*Course*/ course, /*Number*/ startSemester, /*Number*/ endSemester){
            var plan = new models.CoursePlan({
                course : course
            });
            
            
        },
        startManagingCollection : function(/*String*/ type, /*Object*/ collection ){
            var collections = this.managedCollections[type] = this.managedCollections[type] || [];
            collections.push(collection);
            
        },
        stopManagingCollection : function(/*String*/ type, /*Object*/ collection){
            var collections = this.managedCollections[type] = this.managedCollections[type] || [];
            var idx = collections.indexOf(collection);
            if ( idx !== -1 ){
                collections.splice(idx,1);
            }
        },
        sync : function(){
            
            var self = this;
            var q = {
                changes : {
                    Course : {},
                    CourseSection : {},
                    LoadPlan : {},
                    FacultyMember : {},
                    Rank : {},
                    SemesterActivity : {}
                },
                removes : {
                    Course : {},
                    CourseSection: {},
                    LoadPlan : {},
                    FacultyMember : {},
                    Rank : {},
                    SemesterActivity : {}
                },
                adds : {
                    Course : {},
                    CourseSection : {},
                    LoadPlan : {},
                    FacultyMember : {},
                    Rank : {},
                    SemesterActivity : {}
                }
            };
            
            $.each(this.changes, function(type,changes){
                if ( q.changes[type] === undefined ){
                    q.changes[type] = {};
                }
                $.each(changes, function(id, values){
                    q.changes[type][id] = self.propertiesToColumns[type](values);
                });
            });
            
            $.each(this.adds, function(type, adds){
                if ( q.adds[type] === undefined ){
                    q.adds[type] = {};
                }
                $.each(adds, function(id, values){
                    q.adds[type][id] = self.propertiesToColumns[type](values);
                });
            });
            
            $.each(this.removes, function(type, removes){
                if ( q.removes[type] === undefined ){
                    q.removes[type] = {};
                }
                $.each(removes, function(id, values){
                    q.removes[type][id] = self.propertiesToColumns[type](values);
                });
            });
            
            this.addsInProgress = {};
            $.extend(this.addsInProgress, this.adds);
            
            this.removesInProgress = {};
            $.extend(this.removesInProgress, this.removes);
            
            this.changesInProgress = {};
            $.extend(this.changesInProgress, this.changes);
            
            $.each(this.addsInProgress, function(k,v){
                delete self.adds[k];
            });
            
            $.each(this.removesInProgress, function(k,v){
                delete self.removes[k];
            });
            
            $.each(this.changesInProgress, function(k,v){
                delete self.changes[k];
            });
            
            // todo actually connect to server
            
            q = {
                '-action' : 'load_planner_sync',
                '--data' : JSON.stringify(q),
                '--department' : this.department,
                '--state-id' : this.stateId
            };
            
            $.post(DATAFACE_SITE_HREF, q)
                .always(function(data){
                    if ( data && data.code === 200 ){
                        var entities = data.entities || {};
                        var types = ['Rank', 'Course', 'CourseSection', 'FacultyMember', 'LoadPlan','SemesterActivity','CourseInstructor','InstructorPreference'];
                        $.each(types, function(k,type){
                            dict = entities[type];
                            if ( dict === undefined ){
                                return;
                            }
                            var entitiesOfType = self.entities[type] || {};
                            self.entities[type] = entitiesOfType;
                            $.each(dict, function(id, colvals){
                                id = parseInt(id);
                                var propVals = self.columnsToProperties[type](colvals);
                                var existingEntity = entitiesOfType[id];
                                if ( existingEntity === undefined ){
                                    existingEntity = self.newEntity(type, propVals);
                                    
                                } else {
                                    $.extend(existingEntity, propVals);
                                }
                                
                                var newId = self.getEntityId(existingEntity);
                                if ( id !== newId ){
                                    // The ID has changed.
                                    self.removeEntityById(type, id);
                                }
                                self.addEntity(existingEntity); 
                            });
                        });
                        
                        var removed = data.removed || {};
                        
                        $.each(removed, function(type, ids){
                            $.each(ids, function(k,id){
                                var removeInProgress = self.removesInProgress[type][id];
                                if ( removeInProgress !== undefined ){
                                    delete self.removesInProgress[type][id];
                                }
                            });
                        });
                        
                        var added = data.added || {};
                        $.each(added, function(type, ids){
                            $.each(ids, function(oldid,id){
                                var addInProgress = self.addsInProgress[type][oldid];
                                if ( addInProgress !== undefined ){
                                    delete self.addsInProgress[type][oldid];
                                    var entity = self.fetchEntityById(type, oldid);
                                    if ( entity ){
                                        self.setEntityId(entity, id);
                                    }
                                }
                            });
                        });
                        
                        var changed = data.changed || {};
                        $.each(changed, function(type, ids){
                            $.each(ids, function(k,id){
                                var changeInProgress = self.changesInProgress[type][id];
                                if ( changeInProgress !== undefined ){
                                    delete self.changesInProgress[type][id];
                                }
                            });
                        });
                        
                        // Now make sure that all of the changes in progress were
                        // taken care of
                        $.each(self.changesInProgress, function(type, dict){
                            $.each(dict, function(id,changes){
                                throw {code : 500, message : 'Change still in progress after it should be complete'};
                            });
                        });
                        
                        $.each(self.addsInProgress, function(type, dict){
                            $.each(dict, function(id,adds){
                                throw {code : 500, message : 'Add still in progress after it should be complete'};
                            });
                        });
                        
                        $.each(self.removesInProgress, function(type, dict){
                            $.each(dict, function(id,removes){
                                throw {code : 500, message : 'Remove still in progress after it should be complete'};
                            });
                        });
                        
                        // Now to deal with errors
                        var errors = data.errors || {};
                        $.each(errors, function(){
                            $(self).trigger('error', this);
                        });
                        self.stateId = parseInt(data.entities.__id__);
                        
                        if ( models.FacultyMember.TBA === null ){
                            // The TBA faculty member wasn't loaded from the database... we need to add one
                            var tba = self.newFacultyMember({
                                firstName : 'TBA',
                                lastName : 'TBA',
                                employeeNumber : 'TBA',
                            });
                            
                            // We need to create some semester activities for this faculty member
                            // so that it will be picked up by the server queries for this department
                            
                            var tbaSemesterActivity = self.newSemesterActivity({
                                facultyMember : tba,
                                semester : models.Semester.parse(1137),
                                rank : 'P',
                                department1 : self.department
                            });
                            
                            self.addEntity(tba);
                            self.addEntity(tbaSemesterActivity);
                            
                        }
                        $(self).trigger('afterSync');
                    } else if ( data && data.code !== 200 ){
                        // Now to deal with errors
                        console.log(data.message);
                        var errors = data.errors || {};
                        $.each(errors, function(){
                            $(self).trigger('error', this);
                        });
                    } else {
                        $(self).trigger('error', {
                            code : 500,
                            message : 'Error in synchronize step.  Check server for details.'
                        });
                    }
                });
            
        }
        
        
    });
    
})();